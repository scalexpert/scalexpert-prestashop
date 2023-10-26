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
use ScalexpertPlugin\Helper\API\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class KeysTabController extends FrameworkBundleAdminController
{
    const TAB_CLASS_NAME = 'AdminScalexpertPluginKeysTab';

    /**
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $context = \Context::getContext();

        if (!empty($context->language) && \Validate::isLoadedObject($context->language)) {
            $isoCode = strtolower($context->language->iso_code);
        } else {
            $isoCode = 'en';
        }

        $textFormDataHandler = $this->get('scalexpert.form.keys_configuration_form_data_handler');

        $textForm = $textFormDataHandler->getForm();
        $textForm->handleRequest($request);

        if ($textForm->isSubmitted() && $textForm->isValid()) {
            /** You can return array of errors in form handler and they can be displayed to user with flashErrors */
            $errors = $textFormDataHandler->save($textForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('scalexpert_controller_tabs_admin_keys');
            }

            $this->flashErrors($errors);
        }


        return $this->render('@Modules/scalexpertplugin/views/templates/admin/keys.html.twig', [
            'keysConfigurationForm' => $textForm->createView(),
            'findMyKeyLink' => $this->getFindMyKeyLink($isoCode),
        ]);
    }

    public function checkKeysAction(Request $request)
    {
        /** @var Client $apiClient */
        $apiClient = $this->get('scalexpert.api.client');

        $apiClient->setCredentials(
            $request->get('apiKey'),
            $request->get('apiSecret'),
            $request->get('type')
        );

        $response = $apiClient->getBearer(Client::scope_financing . ' ' . Client::scope_insurance);

        die(json_encode($response));
    }

    public function getFindMyKeyLink($isoCode)
    {
        $subscribeLinks = [
            'en' => 'https://dev.scalexpert.societegenerale.com/en/prod/',
            'fr' => 'https://dev.scalexpert.societegenerale.com/fr/prod/',
        ];

        if (isset($subscribeLinks[$isoCode])) {
            return $subscribeLinks[$isoCode];
        }

        return $subscribeLinks['en'];
    }
}
