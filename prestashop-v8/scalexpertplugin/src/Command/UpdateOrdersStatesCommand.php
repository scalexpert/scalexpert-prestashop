<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Command;

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateOrdersStatesCommand extends Command
{
    public const STATUS_OK = 0;

    public const STATUS_ERROR = 1;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('scalexpertplugin:updateOrdersStates')
             ->setDescription('Update orders states');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyContainer = SymfonyContainer::getInstance();
        $updateOrdersStatesService = $symfonyContainer->get('scalexpert.service.update_orders_states');
        $updateOrdersStatesService->updateOrdersStates();

        return self::STATUS_OK;
    }
}