<?php

namespace App\Controller;

use App\Repository\CurrencyRateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CurrencyRateController
 * @package App\Controller
 * @Route("/api")
 */
class CurrencyRateController extends AbstractController
{
    /**
     * @param CurrencyRateRepository $currencyRateRepository
     * @return JsonResponse
     * @Route("/currency_rates", name="currency_rates", methods={"GET"})
     */
    public function getCurrencyRates(CurrencyRateRepository $currencyRateRepository) {
        $currencyRates = $currencyRateRepository->createQueryBuilder('currency_rate')
            ->select('currency.id as id', 'currency.numCode as numCode', 'currency.charCode as charCode', 'currency.name as name', 'currency_rate.value as value', 'MAX(currency_rate.createdAt) as updatedAt')
            ->join("currency_rate.currencyId", "currency")
            ->groupBy('id', 'value')
            ->orderBy('id')
            ->getQuery()
            ->getResult();

        foreach($currencyRates as $i => $currencyRate) {
            $currencyRates[$i]['numCode'] = substr(str_repeat(0, 3) . $currencyRate['numCode'], - 3);
            $currencyRates[$i]['value'] = $currencyRate['value'] / 10000;
        }

        $response = new JsonResponse($currencyRates, 200, array());
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);

        return $response;
    }

    /**
     * @param Request $request
     * @param CurrencyRateRepository $currencyRateRepository
     * @return JsonResponse
     * @Route("/currency_rates_history", name="currency_rates_history", methods={"GET"})
     */
    public function getCurrencyRatesHistory(Request $request, CurrencyRateRepository $currencyRateRepository) {
        $currencyRates = $currencyRateRepository->createQueryBuilder('currency_rate')
            ->select('currency.id as id', 'currency.numCode as numCode', 'currency.charCode as charCode', 'currency.name as name', 'currency_rate.value as value', 'currency_rate.createdAt as time')
            ->join("currency_rate.currencyId", "currency")
            ->andWhere($request && $request->get('char_code') ? 'currency.charCode = \'' . $request->get('char_code') . '\'' : '1 = 1')
            ->andWhere($request && $request->get('dt_from') ? 'currency_rate.createdAt >= \'' . $request->get('dt_from') . '\'' : '1 = 1')
            ->andWhere($request && $request->get('dt_to') ? 'currency_rate.createdAt <= \'' . $request->get('dt_to') . '\'' : '1 = 1')
            ->orderBy('id')
            ->addOrderBy('time', 'DESC')
            ->getQuery()
            ->getResult();

        $result = array();

        foreach($currencyRates as $currencyRate) {
            $currencyId = $currencyRate['id'];
            $datetime = $currencyRate['time'];
            $value = $currencyRate['value'] / 10000;
            $currencyRate['numCode'] = substr(str_repeat(0, 3) . $currencyRate['numCode'], - 3);
            $datetime->value = $value;

            if(!array_key_exists($currencyRate['id'], $result)) {
                unset($currencyRate['id']);
                unset($currencyRate['time']);
                unset($currencyRate['value']);

                $currencyRate['history'][] = $datetime;
                $result[$currencyId] = $currencyRate;
            } else {

                $result[$currencyRate['id']]['history'][] = $datetime;
            }
        }

        $response = new JsonResponse($result, 200, array());
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);

        return $response;
    }
}
