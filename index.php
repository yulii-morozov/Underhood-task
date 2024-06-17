<?php

class TrademarkSearch
{

    const CLIENT_ID = "zHAFtA744S61j1AXXlsHMJFxAohJATiw";
    const CLIENT_SECRET = "pfY6bY6eABg7JDGuyEnMbH1G0XSUZ9bZEGBdq17uH2_TbZ3XCvOIPK-7eUX4jGOV";
    private string $token;
    private string $word;

    public function __construct(string $word)
    {
        $this->word = $word;
    }

    public function getSearchResult(): object
    {
        if ($this->word === '') {
            return (object)['error' => 'Search word is empty'];
        }

        if ($this->getToken()) {
            return $this->searchTrademarks();
        }

        return (object)['error' => 'Token dont created'];

    }

    private function getToken(): bool
    {
        $url = "https://production.api.ipaustralia.gov.au/public/external-token-api/v1/access_token";
        $postData = "grant_type=client_credentials&client_id=" . self::CLIENT_ID . "&client_secret=" . self::CLIENT_SECRET;;
        $header = ['Content-Type: application/x-www-form-urlencoded'];

        $authResponse = $this->getResponse($url, $header, $postData);
        $auth = json_decode($authResponse, true);

        if (isset($auth["access_token"]) && $auth["access_token"] != '') {
            $this->token = $auth["access_token"];
            return true;
        }
        return false;
    }

    public function searchTrademarks(): object
    {
        $tmList = $this->getTmList($this->word);

        $count = $tmList->count;

        $result = (object)[
            'Results' => $count,
        ];

        if ($count > 0) {

            $trademarks = [];

            foreach ($tmList->trademarkIds as $trademarkId) {
                $trademarks[] = $this->getTrademark($trademarkId);
            }

            $result->Data = $trademarks;
        }
        return $result;
    }

    private function getTmList(): object
    {
        $searchUrl = 'https://production.api.ipaustralia.gov.au/public/australian-trade-mark-search-api/v1/search/advanced';
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->token];

        $postData = [
            'sort' => [
                'field' => 'NUMBER',
                'direction' => 'ASCENDING',
            ],
            'rows' => [
                [
                    'op' => 'AND',
                    'query' => [
                        'word' => [
                            'text' => $this->word,
                            'type' => 'PART',
                        ],
                        'wordPhrase' => '',
                    ],
                ],
            ],
        ];

        $tmListRequest = $this->getResponse($searchUrl, $header, json_encode($postData));
        $tmListData = json_decode($tmListRequest);

        return $tmListData;
    }

    private function getTrademark(int $trademarkId): object
    {
        $trademarkUrl = 'https://production.api.ipaustralia.gov.au/public/australian-trade-mark-search-api/v1/trade-mark/' . $trademarkId;
        $header = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->token];
        $trademarkRequest = $this->getResponse($trademarkUrl, $header);
        $trademarkData = json_decode($trademarkRequest);
        $trademarkObj = (object)[
            "number" => $trademarkData->number,
            "url_logo" => $trademarkData->images->images[0] ?? '',
            "name" => implode(' ', $trademarkData->words),
            "class" => $trademarkData->goodsAndServices[0]->class,
            "status" => $trademarkData->statusCode . ": " . $trademarkData->statusDetail,
            "url_details_page" => "https://search.ipaustralia.gov.au/trademarks/search/view/" . $trademarkData->number,
        ];

        return $trademarkObj;
    }

    private function getResponse(string $url, array $header, string $postData = ''): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($postData !== '') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($ch);

        curl_close($ch);
        return $response;
    }
}


if (isset($argv) && isset($argv[1])) {
    $word = trim($argv[1]);
} else {
    $word = '';
}

$trademarkSearch = new TrademarkSearch($word);

print_r($trademarkSearch->getSearchResult());

