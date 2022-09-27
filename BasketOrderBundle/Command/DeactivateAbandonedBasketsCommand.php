<?php

namespace App\BasketOrderBundle\Command;

use App\BasketOrderBundle\Entity\Basket;
use App\BasketOrderBundle\Entity\Order;
use App\BasketOrderBundle\Helper\ShopConst;
use App\SemaphoreBundle\SemaphoreKeyStorage;
use App\SemaphoreBundle\SemaphoreLocker;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeactivateAbandonedBasketsCommand extends Command
{
    const TIME_INTERVAL = 'now - 90 days';

    protected static $defaultName = 'baskets:deactivate';

    protected EntityManager $entityManager;
    private SemaphoreLocker $semaphoreLocker;
    private SemaphoreKeyStorage $semaphoreKeyStorage;
    private LoggerInterface $logger;

    public function __construct(
        string          $name = null,
        LoggerInterface $logger)
    {
        parent::__construct($name);
        $this->logger = $logger;
    }

    public function setVars(
        EntityManager       $entityManager,
        SemaphoreLocker     $semaphoreLocker,
        SemaphoreKeyStorage $semaphoreKeyStorage)
    {
        $this->entityManager = $entityManager;
        $this->semaphoreLocker = $semaphoreLocker;
        $this->semaphoreKeyStorage = $semaphoreKeyStorage;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logStr = 'Error  baskets:deactivate';
        $locker = $this->semaphoreLocker->lock($this->semaphoreKeyStorage::COMMON, self::$defaultName);
        if (!$locker->acquire()) {
            $this->logger->error("{$logStr} double executing");
            return 1;
        }

        $baskets = $this->entityManager->getRepository(Basket::class)->getAbandonedBaskets(self::TIME_INTERVAL);
        foreach ($baskets as $basket) {
            try {
                $this->entityManager->beginTransaction();
                $basket->setActive(ShopConst::NOT_ACTIVE_BASKET);
                $this->entityManager->flush();
                $this->entityManager->commit();
            } catch (\Exception $exception) {
                $this->entityManager->rollback();
                $this->logger->error("{$logStr} {$exception}");
                $locker->release();
                return 1;
            }
        }

        $locker->release();
        return 0;
    }
}