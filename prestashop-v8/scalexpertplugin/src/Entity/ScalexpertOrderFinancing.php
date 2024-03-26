<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
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
