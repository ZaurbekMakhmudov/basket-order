<?php


namespace App\BasketOrderBundle\Service;

use App\BasketOrderBundle\Entity\Delay;
use App\BasketOrderBundle\Helper\DateTimeHelper;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class DelayService
 * @package App\BasketOrderBundle\BasketOrderBundle\Service
 */
class DelayService
{
    /**
     * @var ObjectManager
     */
    private $em;
    /**
     * @var Stopwatch
     */
    private Stopwatch $stopwatch;
    /**
     * @var array
     */
    private array $delays = [];

    public function __construct(
        Stopwatch $stopwatch,
        ManagerRegistry $doctrine
    )
    {
        $this->stopwatch = $stopwatch;
        $this->em = $doctrine->getManager();
    }

    /**
     * @param String $type
     * @return $this
     */
    public function initDelay(String $type) {
        $this->delays[$type] = 0;
        $this->stopwatch->start($type);
        return $this;
    }

    /**
     * @param String $type
     * @return float
     */
    public function getDelay(String $type) {
        return round($this->stopwatch->stop($type)->getDuration() / 1000, 2);
    }

    /**
     * @param float $delay
     * @param String $type
     * @return $this
     */
    public function setDelay(float $delay, String $type) {
        $this->delays[$type] = $delay;
        return $this;
    }

    /**
     * @param $basketId
     * @param String $type
     * @return $this
     */
    public function insDelay($basketId, String $type) {
        $delay = new Delay();
        $delay->setExecuted(DateTimeHelper::getInstance()->getDateCurrent());
        $delay->setExecutedExactly(microtime(true));
        $delay->setRequest($type);
        $delay->setBasketId($basketId);
        $delay->setDelay($this->delays[$type]);
        $this->em->persist($delay);
        $this->em->flush();
        return $this;
    }

    /**
     * @param $basketId
     * @param String $type
     * @return $this
     */
    public function finishDelay($basketId, String $type) {
        $this->setDelay($this->getDelay($type), $type)->insDelay($basketId, $type);
        return $this;
    }

    /**
     * @return ObjectRepository
     */
    public function getRepo() {
        return $this->em->getRepository(Delay::class);
    }
}