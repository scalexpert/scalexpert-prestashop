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

use PrestaShop\PrestaShop\Core\Form\Handler;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use ScalexpertPlugin\Handler\SolutionNameHandler;
use ScalexpertPlugin\Helper\ConfigChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigTabController extends FrameworkBundleAdminController
{
    const TAB_CLASS_NAME = 'AdminScalexpertPluginConfigTab';

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

        $context = \Context::getContext();

        if (!empty($context->language) && \Validate::isLoadedObject($context->language)) {
            $isoCode = strtolower($context->language->iso_code);
        } else {
            $isoCode = 'en';
        }

        $regroupPaymentsFormDataHandler = $this->get('scalexpert.form.regroup_payments_configuration_form_data_handler');

        $regroupPaymentsForm = $regroupPaymentsFormDataHandler->getForm();
        $regroupPaymentsForm->handleRequest($request);

        if ($regroupPaymentsForm->isSubmitted() && $regroupPaymentsForm->isValid()) {
            $errors = $regroupPaymentsFormDataHandler->save($regroupPaymentsForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('scalexpert_controller_tabs_admin_active');
            }

            $this->flashErrors($errors);
        }

        /** @var Handler $financingFormDataHandler */
        $financingFormDataHandler = $this->get('scalexpert.form.financing_configuration_form_data_handler');

        $financingForm = $financingFormDataHandler->getForm();
        $financingForm->handleRequest($request);

        if ($financingForm->isSubmitted() && $financingForm->isValid()) {
            $errors = $financingFormDataHandler->save($financingForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('scalexpert_controller_tabs_admin_active');
            }

            $this->flashErrors($errors);
        }

        $insuranceFormDataHandler = $this->get('scalexpert.form.insurance_configuration_form_data_handler');

        $insuranceForm = $insuranceFormDataHandler->getForm();
        $insuranceForm->handleRequest($request);

        if ($insuranceForm->isSubmitted() && $insuranceForm->isValid()) {
            $errors = $insuranceFormDataHandler->save($insuranceForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('scalexpert_controller_tabs_admin_active');
            }

            $this->flashErrors($errors);
        }


        $hasMissingFinancialSolutions = false;

        foreach ($financingForm->all() as $fieldName => $data) {
            if (str_contains($fieldName, 'DISABLE')) {
                $hasMissingFinancialSolutions = true;
            }
        }

        $hasMissingInsuranceSolutions = false;

        foreach ($insuranceForm->all() as $fieldName => $data) {
            if (str_contains($fieldName, 'DISABLE')) {
                $hasMissingInsuranceSolutions = true;
            }
        }

        return $this->render('@Modules/scalexpertplugin/views/templates/admin/config.html.twig', [
            'regroupPaymentsForm' => $regroupPaymentsForm->createView(),
            'financingConfigurationForm' => $financingForm->createView(),
            'insuranceConfigurationForm' => $insuranceForm->createView(),
            'hasMissingFinancialSolutions' => $hasMissingFinancialSolutions,
            'hasMissingInsuranceSolutions' => $hasMissingInsuranceSolutions,
            'subscribeToFinancingOptionsLink' => $this->getSubscribeLink($isoCode),
            'subscribeToInsuranceOptionsLink' => $this->getSubscribeLink($isoCode, 'insurance'),
        ]);
    }

    public function getSubscribeLink($isoCode, $solutionType = 'financial')
    {
        $subscribeLinks = [
            'fr' => [
                'financial' => 'https://scalexpert.societegenerale.com/app/fr/page/e-financement',
                'insurance' => 'https://scalexpert.societegenerale.com/app/fr/page/garantie',
            ],
            'en' => [
                'financial' => 'https://scalexpert.societegenerale.com/app/en/page/e-financing',
                'insurance' => 'https://scalexpert.societegenerale.com/app/en/page/warranty',
            ],
        ];

        if (isset($subscribeLinks[$isoCode])) {
            return $subscribeLinks[$isoCode][$solutionType];
        }

        return $subscribeLinks['en'][$solutionType];
    }
}
