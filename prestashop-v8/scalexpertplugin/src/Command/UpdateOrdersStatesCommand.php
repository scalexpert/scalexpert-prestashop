<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Scalexpert (https://scalexpert.societegenerale.com/)
 * @copyright Scalexpert
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace ScalexpertPlugin\Command;

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use ScalexpertPlugin\Service\UpdateOrdersStatesService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateOrdersStatesCommand extends Command
{
    public const STATUS_OK = 0;

    public const STATUS_ERROR = 1;

    protected function configure(): void
    {
        $this->setName('scalexpertplugin:updateOrdersStates')
            ->setDescription('Update orders states');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $symfonyContainer = SymfonyContainer::getInstance();
            /* @var UpdateOrdersStatesService $updateOrdersStatesService */
            $updateOrdersStatesService = $symfonyContainer->get('scalexpert.service.update_orders_states');
            $updateOrdersStatesService->updateOrdersStates();
        } catch (\Exception $exception) {
            echo $exception->getMessage();

            return self::STATUS_ERROR;
        }

        return self::STATUS_OK;
    }

}
