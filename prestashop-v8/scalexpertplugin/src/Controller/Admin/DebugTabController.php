<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for PrestaShop.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */

declare(strict_types=1);

namespace ScalexpertPlugin\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use ScalexpertPlugin\Helper\ConfigChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugTabController extends FrameworkBundleAdminController
{
    const TAB_CLASS_NAME = 'AdminScalexpertPluginDebugTab';

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

        $debugFormDataHandler = $this->get('scalexpert.form.debug_configuration_form_data_handler');
        $debugForm = $debugFormDataHandler->getForm();
        $debugForm->handleRequest($request);

        if ($debugForm->isSubmitted() && $debugForm->isValid()) {
            $errors = $debugFormDataHandler->save($debugForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('scalexpert_controller_tabs_admin_debug');
            }

            $this->flashErrors($errors);
        }

        return $this->render(
            '@Modules/scalexpertplugin/views/templates/admin/debug.html.twig',
            [
                'debugConfigurationForm' => $debugForm->createView(),
            ]
        );
    }
}
