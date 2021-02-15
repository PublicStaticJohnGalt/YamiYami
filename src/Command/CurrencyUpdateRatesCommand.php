<?php

namespace App\Command;

use App\Entity\Currency;
use App\Entity\CurrencyRate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CurrencyUpdateRatesCommand extends Command
{
    protected static $defaultName = 'currency:update-rates';
    protected static $defaultDescription = 'Command updates currency rates from provided XML feed';
    protected static $xmlFeed = 'http://www.cbr.ru/scripts/XML_daily.asp';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $xml = simplexml_load_string(file_get_contents(self::$xmlFeed), 'SimpleXMLElement', LIBXML_NOCDATA);
        $currencies = json_decode(json_encode((array)$xml))->Valute;
        $currencyRepository = $this->entityManager->getRepository('App:Currency');
        $currencyRateRepository = $this->entityManager->getRepository('App:CurrencyRate');

        foreach($currencies as $currency) {
            if(!$entity = $currencyRepository->findOneBy(array('externalId' => $currency->{'@attributes'}->ID))) {
                $currencyObject = new Currency();
                $currencyObject->setExternalId($currency->{'@attributes'}->ID);
                $currencyObject->setNumCode($currency->NumCode);
                $currencyObject->setCharCode($currency->CharCode);
                $currencyObject->setName($currency->Name);
                $this->entityManager->persist($currencyObject);
                $this->entityManager->flush();
                $entity = $currencyObject;

                $io->note('New currency (ID:' . $currency->{'@attributes'}->ID . ') created');
            }

            $currentRate = (string)(str_replace(',', '.', $currency->Value) * 10000);
            $lastCurrencyRateRecord = $currencyRateRepository->findBy(
                array('currencyId' => $entity->getId()),
                array('id' => 'DESC'),
                1
            );

            if(!$lastCurrencyRateRecord || $lastCurrencyRateRecord[0]->getValue() != $currentRate) {
                $currencyRateObject = new CurrencyRate();
                $currencyRateObject->setValue($currentRate);
                $currencyRateObject->setCurrencyId($entity);
                $currencyRateObject->setCreatedAt(new \DateTime("now"));
                $this->entityManager->persist($currencyRateObject);
                $this->entityManager->flush();
            }
        }

        $io->success('All currencies rates successfully updated');

        return Command::SUCCESS;
    }
}
