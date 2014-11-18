<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace ONGR\AdminBundle\Controller;

use ONGR\CookiesBundle\Cookie\Model\JsonCookie;
use ONGR\AdminBundle\Form\Type\LoginType;
use ONGR\AdminBundle\Security\Authentication\Cookie\SessionlessAuthenticationCookieService;
use ONGR\AdminBundle\Security\Authentication\Provider\SessionlessAuthenticationProvider;
use ONGR\AdminBundle\Security\Authentication\Token\SessionlessToken;
use ONGR\AdminBundle\Security\Core\SessionlessSecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for logging in and out a user.
 */
class UserController extends Controller
{

    /**
     * Login action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request)
    {
        // Check if already logged in.
        $alreadyLoggedIn = $this->getSecurityContext()->getToken() instanceof SessionlessToken;

        // Handle form.
        $loginData = [];
        $form = $this->createForm(new LoginType(), $loginData);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $redirectResponse = $this->redirect($this->generateUrl('ongr_admin_sessionless_login'));
            $loginData = $form->getData();

            $username = $loginData['username'];
            $password = $loginData['password'];

            if ($this->getAuthProvider()->matchUsernameAndPassword($username, $password)) {
                $ipAddress = $request->getClientIp();
                $cookieValue = $this->getAuthCookieService()->create($username, $password, $ipAddress);

                $cookie = $this->getAuthenticationCookie();
                $cookie->setValue($cookieValue);

                return $redirectResponse;
            }
        }

        // Render.
        return $this->render(
            'ONGRAdminBundle:User:login.html.twig',
            ['form' => $form->createView(), 'is_logged_in' => $alreadyLoggedIn]
        );
    }

    /**
     * Logout action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function logoutAction(
        /** @noinspection PhpUnusedParameterInspection */
        Request $request
    ) {
        $cookie = $this->getAuthenticationCookie();
        $cookie->setClear(true);
        $response = $this->redirect($this->generateUrl('ongr_admin_sessionless_login'));

        return $response;
    }

    /**
     * @return SessionlessAuthenticationCookieService
     */
    protected function getAuthCookieService()
    {
        return $this->authCookieService = $this->get(
            'ongr_admin.authentication.authentication_cookie_service'
        );
    }

    /**
     * @return SessionlessAuthenticationProvider
     */
    protected function getAuthProvider()
    {
        return $this->authenticationProvider = $this->get(
            'ongr_admin.authentication.sessionless_authentication_provider'
        );
    }

    /**
     * @return SessionlessSecurityContext
     */
    protected function getSecurityContext()
    {
        return $this->get('ongr_admin.authentication.sessionless_security_context');
    }

    /**
     * @return JsonCookie
     */
    protected function getAuthenticationCookie()
    {
        return $this->get('ongr_admin.authentication.authentication_cookie');
    }
}