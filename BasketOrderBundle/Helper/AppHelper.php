<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 29.11.19
 * Time: 21:21
 */

namespace App\BasketOrderBundle\Helper;


use App\BasketOrderBundle\Entity\Order;
use Symfony\Component\HttpFoundation\Response;

class AppHelper
{
    /**
     * @param array $array
     * @return string
     */
    static public function jsonFromArray($array = [])
    {
        $out = json_encode($array, JSON_UNESCAPED_UNICODE);

        return $out;
    }

    /**
     * @param null $json
     * @return mixed
     */
    static public function arrayFromJson($json = null)
    {
        $out = json_decode($json, true);

        return $out;
    }

    /**
     * @param Order $order
     * @param $postData
     * @param $url
     * @param $port
     * @return array
     */
    static public function sendImitationCurlData(Order $order, $postData, $url, $port)
    {
        $optionCurl = array(
            CURLOPT_PORT => $port, //"54654",
            CURLOPT_URL => $url, //"http://webrm.tdera.ru:54654/api/pickupinstore/ordercreate",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json"
            ),
        );
        $response = '{"data":[{"NOM_ZAK":"'.$order->getOrderId().'"}],"errormessage":{"error":0,"error_msg":null}}';
        $err = '{"data":[{"NOM_ZAK":"UR-19190-233"}],"errormessage":{"error":1,"error_msg":"order is already has"}}';
        //IS_IMITATION_CURL_ERROR

        if (ShopConst::IS_IMITATION_CURL_RM == 'error') {
            $out = [
                'is_imitation' => ShopConst::IS_IMITATION_CURL,
                'imitation' => ShopConst::IS_IMITATION_CURL_RM,
                'result' => false,
                'error' => $err,
                'response' => null,
                'optionCurl' => $optionCurl,
            ];
        } elseif (ShopConst::IS_IMITATION_CURL_RM == 'success') {
            $out = [
                'is_imitation' => ShopConst::IS_IMITATION_CURL,
                'imitation' => ShopConst::IS_IMITATION_CURL_RM,
                'result' => true,
                'error' => null,
                'response' => $response, // "{\"data\":[{\"NOM_ZAK\":\"UR-19385-182\"}],\"errormessage\":{\"error\":0,\"error_msg\":null}}"
                'optionCurl' => $optionCurl,
            ];
        } else {
            throw new \Exception('Not found value Response = ', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $out['url'] = $url;
        $out['port'] = $port;

        return $out;
    }

    /**
     * @param $request
     * @param $url
     * @param null $postData
     * @param null $headers
     * @return array
     */
    static public function getCurlOptions($request, $url, $postData = null, $headers = null)
    {
        $options = [
            CURLOPT_CUSTOMREQUEST => $request,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            /* ToDo
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode($postData),
                        CURLOPT_HTTPHEADER => $headers,
            */
        ];
        return $options;
    }

    /**
     * @param $value
     * @param int $round
     * @return string
     */
    static public function getDigitalRound($value, $round=2)
    {
        //$value = $value * 1.57;
        //$value = (float)$value;
        //$value = round($value,$round);
        $value = number_format($value ,2,'.','');
        $value = (string)$value;

        return $value;
    }
}