<?php

namespace Every8d;

use Every8d\Exception\BadResponseException;
use Every8d\Message\MMS;
use Every8d\Message\SMS;

class Api
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return float
     * @throws Exception\BadResponseException
     * @throws Exception\ErrorResponseException
     * @throws Exception\NotFoundException
     * @throws Exception\UnexpectedStatusCodeException
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    public function getCredit(): float
    {
        $request = $this->client->newFormRequest('API21/HTTP/getCredit.ashx');
        $response = $this->client->send($request);
        $contents = $response->getBody()->getContents();

        return (float)$contents;
    }

    /**
     * @param string $uri
     * @param array $formData
     * @return array
     * @throws Exception\BadResponseException
     * @throws Exception\ErrorResponseException
     * @throws Exception\NotFoundException
     * @throws Exception\UnexpectedStatusCodeException
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    protected function send(string $uri, array $formData): array
    {
        $request = $this->client->newFormRequest($uri, $formData);
        $response = $this->client->send($request);
        $contents = $response->getBody()->getContents();
        $record = str_getcsv($contents, ',');

        return [
            'Credit' => (float)$record[0],
            'Sent' => (int)$record[1],
            'Cost' => (float)$record[2],
            'Unsent' => (int)$record[3],
            'BatchID' => $record[4],
        ];
    }

    /**
     * @param SMS $sms
     * @return array
     * @throws Exception\BadResponseException
     * @throws Exception\NotFoundException
     * @throws Exception\UnexpectedStatusCodeException
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    public function sendSMS(SMS $sms): array
    {
        return $this->send('API21/HTTP/sendSMS.ashx', $sms->buildFormData());
    }

    /**
     * @param MMS $mms
     * @return array
     * @throws Exception\BadResponseException
     * @throws Exception\ErrorResponseException
     * @throws Exception\NotFoundException
     * @throws Exception\UnexpectedStatusCodeException
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    public function sendMMS(MMS $mms): array
    {
        return $this->send('API21/HTTP/MMS/sendMMS.ashx', $mms->buildFormData());
    }

    /**
     * @param string $batchID
     * @param int|null $pageNo
     * @return array
     * @throws Exception\BadResponseException
     * @throws Exception\ErrorResponseException
     * @throws Exception\NotFoundException
     * @throws Exception\UnexpectedStatusCodeException
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    public function getDeliveryStatusBySMS(string $batchID, int $pageNo = null): array
    {
        return $this->getDeliveryStatus('API21/HTTP/getDeliveryStatus.ashx', $batchID, $pageNo);
    }

    /**
     * @param string $batchID
     * @param int|null $pageNo
     * @return array
     * @throws Exception\BadResponseException
     * @throws Exception\ErrorResponseException
     * @throws Exception\NotFoundException
     * @throws Exception\UnexpectedStatusCodeException
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    public function getDeliveryStatusByMMS(string $batchID, int $pageNo = null): array
    {
        return $this->getDeliveryStatus('API21/HTTP/MMS/getDeliveryStatus.ashx', $batchID, $pageNo);
    }

    /**
     * @param string $uri
     * @param string $batchID
     * @param int|null $pageNo
     * @return array
     * @throws Exception\BadResponseException
     * @throws Exception\ErrorResponseException
     * @throws Exception\NotFoundException
     * @throws Exception\UnexpectedStatusCodeException
     * @throws \Exception
     * @throws \Http\Client\Exception
     */
    protected function getDeliveryStatus(string $uri, string $batchID, int $pageNo = null): array
    {
        $formData = ['BID' => $batchID];
        if ($pageNo !== null) {
            $formData['PNO'] = $pageNo;
        }

        $request = $this->client->newFormRequest($uri, $formData);
        $response = $this->client->send($request);
        $contents = $response->getBody()->getContents();
        $lines = explode("\n", $contents);

        if (count($lines) < 2) {
            throw new BadResponseException('Invalid delivery status');
        }

        $count = (int)$lines[0];
        $records = [];

        foreach ($lines as $line) {
            $record = str_getcsv($line, "\t");
            if (count($record) === 5) {
                $records[] = [
                    'Name' => $record[0],
                    'Mobile' => $record[1],
                    'SendTime' => $record[2],
                    'Cost' => (float)$record[3],
                    'Status' => (int)$record[4],
                ];
            }
        }

        return [
            'Count' => $count,
            'Records' => $records,
        ];
    }
}