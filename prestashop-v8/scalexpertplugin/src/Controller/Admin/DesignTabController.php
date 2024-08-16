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

namespace ScalexpertPlugin\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use ScalexpertPlugin\Form\Configuration\FinancingConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\InsuranceConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Customize\DesignCustomizeFormDataConfiguration;
use ScalexpertPlugin\Helper\API\Client;
use ScalexpertPlugin\Helper\ConfigChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DesignTabController extends FrameworkBundleAdminController
{
    const TAB_CLASS_NAME = 'AdminScalexpertPluginDesignTab';

    /**
     * @return Response
     */
    public function indexAction(Request $request)
    {
        /** @var ConfigChecker $configChecker */
        $configChecker = $this->get('scalexpert.conf.config_checker');
        if (!$configChecker->checkExistingKeys()) {
            return $this->render(
                '@Modules/scalexpertplugin/views/templates/admin/missing_keys_error.html.twig'
            );
        }

        /* @var Client $apiClient */
        $apiClient = $this->get('scalexpert.api.client');
        $financialSolutions = $apiClient->getAllFinancialSolutions();
        $insuranceSolutions = $apiClient->getAllInsuranceSolutions();
        $allSolutions = $this->fillSolutions(array_merge($financialSolutions, $insuranceSolutions));

        $activeLink = \Context::getContext()->link->getAdminLink(
            'AdminScalexpertPluginConfigTab',
            true,
            [
                'route' => 'scalexpert_controller_tabs_admin_active'
            ]
        );

        $designFormDataHandler = $this->get('scalexpert.form.design_customize_form_data_handler');
        $designForm = $designFormDataHandler->getForm();
        $designForm->handleRequest($request);

        if ($designForm->isSubmitted() && $designForm->isValid()) {
            $errors = $designFormDataHandler->save($designForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
                return $this->redirectToRoute('scalexpert_controller_tabs_admin_design');
            }

            $this->flashErrors($errors);
        }

        $template = '@Modules/scalexpertplugin/views/templates/admin/design.html.twig';
        if (version_compare(_PS_VERSION_, '8.1.0', '>=')) {
            $template = '@Modules/scalexpertplugin/views/templates/admin/ps81/design.html.twig';
        }

        return $this->render(
            $template,
            [
                'designConfigurationForm' => $designForm->createView(),
                'allSolutions' => $allSolutions,
                'activeLink' => $activeLink,
            ]
        );
    }

    private function fillSolutions(
        array $allSolutions
    ): array
    {
        if (empty($allSolutions)) {
            return [];
        }

        $financing = json_decode(
            $this->configuration->get(FinancingConfigurationFormDataConfiguration::CONFIGURATION_FINANCING, '{}'),
            true
        );
        $insurance = json_decode(
            $this->configuration->get(InsuranceConfigurationFormDataConfiguration::CONFIGURATION_INSURANCE, '{}'),
            true
        );
        $configurationSolutions = array_merge($financing, $insurance);

        foreach ($allSolutions as $solutionCode => &$solution) {
            $solution['active'] = $configurationSolutions[$solutionCode] ?? false;

            $solution['generalFields'] = [
                sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'excludedCategories'),
                sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'excludedProducts'),
                sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'position'),
            ];

            if ('insurance' === $solution['type']) {
                $solution['productFields'] = [
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'productDisplay'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'productTitle'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'productSubtitle'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'productPosition'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'productDisplayLogo'),
                ];

                $solution['cartFields'] = [
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'cartDisplay'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'cartTitle'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'cartPosition'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'cartDisplayLogo'),
                ];
            } else {
                $solution['productFields'] = [
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'productDisplay'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'productTitle'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'productPosition'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'productDisplayLogo'),
                ];

                $solution['cartFields'] = [
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'cartDisplay'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'cartTitle'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'cartPosition'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'cartDisplayLogo'),
                ];

                $solution['paymentFields'] = [
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'paymentTitle'),
                    sprintf('%s' . DesignCustomizeFormDataConfiguration::ID_DELIMITER . '%s', $solutionCode, 'paymentDisplayLogo'),
                ];
            }

            $solutionNameHandler = $this->get('scalexpert.handler.solution_name');
            $solutionName = $solutionNameHandler->getSolutionName($solutionCode);

            if (!empty($solutionName)) {
                $solution['visualTitle'] = $solutionName;
            }
        }

        return $allSolutions;
    }
}
