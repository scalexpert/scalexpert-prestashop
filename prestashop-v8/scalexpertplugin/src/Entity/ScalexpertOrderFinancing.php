<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table()
 */
class ScalexpertOrderFinancing
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_order", type="integer")
     */
    private $idOrder;

    /**
     * @var string|null
     *
     * @ORM\Column(name="id_subscription", type="string")
     * Subscription id
     */
    private $idSubscription = null;

    public function getIdOrder(): int
    {
        return $this->idOrder;
    }

    public function setIdOrder(int $idOrder): void
    {
        $this->idOrder = $idOrder;
    }

    public function getIdSubscription(): ?string
    {
        return $this->idSubscription;
    }

    public function setIdSubscription(?string $idSubscription): void
    {
        $this->idSubscription = $idSubscription;
    }
}