<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\CarRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
        private CarRepository $carRepository,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $identifier = trim((string) $request->request->get('username', ''));
        $password = (string) $request->request->get('password', '');
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $identifier);

        return new Passport(
            new UserBadge($identifier, function (string $userIdentifier): User {
                $user = $this->userRepository->findUserFlexible($userIdentifier);

                if (!$user instanceof User) {
                    throw new CustomUserMessageAuthenticationException('Incorrect email or password.');
                }

                // dili pa-log-inon kung gi-disable sa admin ang account
                if (0 === $user->getIsActive()) {
                    throw new CustomUserMessageAuthenticationException('This account has been disabled. Please contact the admin.');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = (string) $request->request->get('_target_path', '');
        if ('' !== $targetPath && str_starts_with($targetPath, '/')) {
            return new RedirectResponse($targetPath);
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $user = $token->getUser();
        if ($user instanceof User) {
            if ($user->isAdmin()) {
                return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
            }
            if ($user->isStaff()) {
                return new RedirectResponse($this->urlGenerator->generate('admin_product'));
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
