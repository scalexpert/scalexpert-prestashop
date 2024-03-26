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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class KeysTabController extends FrameworkBundleAdminController
{
    const TAB_CLASS_NAME = 'AdminScalexpertPluginKeysTab';

    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $context = \Context::getContext();

        if (
            null !== $context
            && !empty($context->language)
            && \Validate::isLoadedObject($context->language)
        ) {
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

        $response = $apiClient->getBearer(Client::SCOPE_FINANCING . ' ' . Client::SCOPE_INSURANCE);
        return new JsonResponse(json_encode($response), 200, [], true);
    }

    public function getFindMyKeyLink($isoCode)
    {
        $subscribeLinks = [
            'en' => 'https://dev.scalexpert.societegenerale.com/en/prod/',
            'fr' => 'https://dev.scalexpert.societegenerale.com/fr/prod/',
        ];

        return $subscribeLinks[$isoCode] ?? $subscribeLinks['en'];
    }
}
