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
class ScalexpertProductCustomField
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_product", type="integer")
     */
    private $idProduct;

    /**
     * @var string|null
     *
     * @ORM\Column(name="model", type="string")
     *  Product model
     */
    private $model = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="characteristics", type="string")
     *  Product characteristics
     */
    private $characteristics = null;

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
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * @param string|null $model
     */
    public function setModel(?string $model): void
    {
        $this->model = $model;
    }

    /**
     * @return string|null
     */
    public function getCharacteristics(): ?string
    {
        return $this->characteristics;
    }

    /**
     * @param string|null $characteristics
     */
    public function setCharacteristics(?string $characteristics): void
    {
        $this->characteristics = $characteristics;
    }
}
