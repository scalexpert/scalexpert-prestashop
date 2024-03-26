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
use ScalexpertPlugin\Helper\API\Client;
use ScalexpertPlugin\Helper\ConfigChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MappingTabController extends FrameworkBundleAdminController
{
    const TAB_CLASS_NAME = 'AdminScalexpertPluginMappingTab';

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

        $mappingFormDataHandler = $this->get('scalexpert.form.mapping_configuration_form_data_handler');
        $mappingForm = $mappingFormDataHandler->getForm();
        $mappingForm->handleRequest($request);

        if ($mappingForm->isSubmitted() && $mappingForm->isValid()) {
            $errors = $mappingFormDataHandler->save($mappingForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('scalexpert_controller_tabs_admin_mapping');
            }

            $this->flashErrors($errors);
        }

        $maintenanceURL = \Context::getContext()->link->getModuleLink(
            'scalexpertplugin',
            'maintenance'
        );

        $infoURL = 'https://docs.scalexpert.societegenerale.com/apidocs/3mLlrPx3sPtekcQvEEUg/for-discovery/credit/e-financing-status-life-cycle';

        return $this->render(
            '@Modules/scalexpertplugin/views/templates/admin/mapping.html.twig',
            [
                'mappingConfigurationForm' => $mappingForm->createView(),
                'maintenanceURL' => $maintenanceURL,
                'infoURL' => $infoURL,
            ]
        );
    }
}
