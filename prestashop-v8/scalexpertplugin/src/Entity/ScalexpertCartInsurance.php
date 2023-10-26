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
class ScalexpertCartInsurance
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_cart_insurance", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $idCartInsurance;

    /**
     * @var int
     * @ORM\Column(name="id_product", type="integer")
     */
    private $idProduct;

    /**
     * @var int|null
     * @ORM\Column(name="id_product_attribute", type="integer")
     */
    private $idProductAttribute  = null;

    /**
     * @var string|null
     * @ORM\Column(name="quotations", type="string")
     */
    private $quotations = null;

    /**
     * @var int|null
     * @ORM\Column(name="id_insurance_product", type="integer")
     */
    private $idInsuranceProduct;

    /**
     * @var int
     * @ORM\Column(name="id_cart", type="integer")
     */
    private $idCart;

    /**
     * @var string
     * @ORM\Column(name="id_item", type="string")
     */
    private $idItem;

    /**
     * @var string
     * @ORM\Column(name="id_insurance", type="string")
     */
    private $idInsurance;

    /**
     * @var string
     * @ORM\Column(name="solution_code", type="string")
     */
    private $solutionCode;

    /**
     * @var string|null
     * @ORM\Column(name="subscriptions", type="string")
     */
    private $subscriptions = null;

    /**
     * @var bool
     * @ORM\Column(name="subscriptions_processed", type="boolean")
     */
    private $subscriptionsProcessed = false;

    /**
     * @return int
     */
    public function getIdCartInsurance(): int
    {
        return $this->idCartInsurance;
    }

    /**
     * @return int
     */
    public function getIdProduct(): int
    {
        return $this->idProduct;
    }

    /**
     * @param int $idProduct
     */
    public function setIdProduct(int $idProduct): void
    {
        $this->idProduct = $idProduct;
    }

    /**
     * @return int|null
     */
    public function getIdProductAttribute(): ?int
    {
        return $this->idProductAttribute;
    }

    /**
     * @param int|null $idProductAttribute
     */
    public function setIdProductAttribute(?int $idProductAttribute): void
    {
        $this->idProductAttribute = $idProductAttribute;
    }

    /**
     * @return array|null
     */
    public function getQuotations(): ?array
    {
        if (!empty($this->quotations)) {
            return json_decode($this->quotations, true);
        }

        return null;
    }

    /**
     * @param array|null $quotations
     */
    public function setQuotations(?array $quotations): void
    {
        if (!empty($quotations)) {
            $this->quotations = json_encode($quotations);
        } else {
            $this->quotations = null;
        }
    }

    /**
     * @return int
     */
    public function getIdInsuranceProduct(): ?int
    {
        return $this->idInsuranceProduct;
    }

    /**
     * @param int|null $idInsuranceProduct
     */
    public function setIdInsuranceProduct(?int $idInsuranceProduct): void
    {
        $this->idInsuranceProduct = $idInsuranceProduct;
    }

    /**
     * @return int
     */
    public function getIdCart(): int
    {
        return $this->idCart;
    }

    /**
     * @param int $idCart
     */
    public function setIdCart(int $idCart): void
    {
        $this->idCart = $idCart;
    }

    /**
     * @return string
     */
    public function getIdItem(): string
    {
        return $this->idItem;
    }

    /**
     * @param string $idItem
     */
    public function setIdItem(string $idItem): void
    {
        $this->idItem = $idItem;
    }

    /**
     * @return string
     */
    public function getIdInsurance(): string
    {
        return $this->idInsurance;
    }

    /**
     * @param string $idInsurance
     */
    public function setIdInsurance(string $idInsurance): void
    {
        $this->idInsurance = $idInsurance;
    }

    /**
     * @return string
     */
    public function getSolutionCode(): string
    {
        return $this->solutionCode;
    }

    /**
     * @param string $solutionCode
     */
    public function setSolutionCode(string $solutionCode): void
    {
        $this->solutionCode = $solutionCode;
    }

    /**
     * @return array|null
     */
    public function getSubscriptions(): ?array
    {
        if (!empty($this->subscriptions)) {
            return json_decode($this->subscriptions, true);
        }

        return null;
    }

    /**
     * @param array|null $subscriptions
     */
    public function setSubscriptions(?array $subscriptions): void
    {
        if (!empty($subscriptions)) {
            $this->subscriptions = json_encode($subscriptions);
        } else {
            $this->subscriptions = null;
        }
    }

    public function isSubscriptionsProcessed(): bool
    {
        return $this->subscriptionsProcessed;
    }

    public function setSubscriptionsProcessed(bool $subscriptionsProcessed): void
    {
        $this->subscriptionsProcessed = $subscriptionsProcessed;
    }
}