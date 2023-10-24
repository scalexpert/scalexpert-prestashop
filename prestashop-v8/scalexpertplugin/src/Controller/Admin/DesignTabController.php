<?php

declare(strict_types=1);

namespace ScalexpertPlugin\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use ScalexpertPlugin\Form\Configuration\FinancingConfigurationFormDataConfiguration;
use ScalexpertPlugin\Form\Configuration\InsuranceConfigurationFormDataConfiguration;
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

        $designFormDataHandler = $this->get('scalexpert.form.design_customize_form_data_handler');
        $designForm = $designFormDataHandler->getForm();
        $designForm->handleRequest($request);

        $apiClient = $this->get('scalexpert.api.client');
        $financialSolutions = $apiClient->getAllFinancialSolutions();
        $insuranceSolutions = $apiClient->getAllInsuranceSolutions();
        $allSolutions = array_merge($financialSolutions, $insuranceSolutions);

        if (!empty($allSolutions)) {
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
                    sprintf('%s:%s', $solutionCode, 'excluded_categories'),
                ];

                if (!empty($solution) && $solution['type'] === 'insurance') {
                    $solution['productFields'] = [
                        sprintf('%s:%s', $solutionCode, 'product_display'),
                        sprintf('%s:%s', $solutionCode, 'product_title'),
                        sprintf('%s:%s', $solutionCode, 'product_subtitle'),
                        sprintf('%s:%s', $solutionCode, 'product_position'),
                        sprintf('%s:%s', $solutionCode, 'product_display_logo'),
                    ];
                } else {
                    $solution['productFields'] = [
                        sprintf('%s:%s', $solutionCode, 'product_display'),
                        sprintf('%s:%s', $solutionCode, 'product_title'),
                        sprintf('%s:%s', $solutionCode, 'product_position'),
                        sprintf('%s:%s', $solutionCode, 'product_display_logo'),
                    ];

                    $solution['paymentFields'] = [
                        sprintf('%s:%s', $solutionCode, 'payment_title'),
                        sprintf('%s:%s', $solutionCode, 'payment_display_logo'),
                    ];
                }

                if (!empty($solution) && $solution['type'] === 'insurance') {
                    $solution['cartFields'] = [
                        sprintf('%s:%s', $solutionCode, 'cart_display'),
                        sprintf('%s:%s', $solutionCode, 'cart_title'),
                        sprintf('%s:%s', $solutionCode, 'cart_subtitle'),
                        sprintf('%s:%s', $solutionCode, 'cart_position'),
                        sprintf('%s:%s', $solutionCode, 'cart_display_logo'),
                    ];
                }

                $solutionNameHandler = $this->get('scalexpert.handler.solution_name');
                $solutionName = $solutionNameHandler->getSolutionName($solutionCode);

                if (!empty($solutionName)) {
                    $solution['visualTitle'] = $solutionName;
                }
            }
        }

        $context = \Context::getContext();
        $activeLink = $context->link->getAdminLink(
            'AdminScalexpertPluginConfigTab',
            true,
            [
                'route' => 'scalexpert_controller_tabs_admin_active'
            ]
        );

        if ($designForm->isSubmitted() && $designForm->isValid()) {
            $errors = $designFormDataHandler->save($designForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('scalexpert_controller_tabs_admin_design');
            }

            $this->flashErrors($errors);
        }

        return $this->render(
            '@Modules/scalexpertplugin/views/templates/admin/design.html.twig',
            [
                'designConfigurationForm' => $designForm->createView(),
                'allSolutions' => $allSolutions,
                'activeLink' => $activeLink,
            ]
        );
    }
}