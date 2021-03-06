<?php

namespace Lucario\Controller;

use Lucario\Session\Session;
use Lucario\Session\SessionInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class AbstractController
{
    protected string $templatesDirectory;
    protected Environment $templateEngine;
    protected array $errors;
    private ?SessionInterface $session;
    private ?string $csrfToken;

    public function __construct()
    {
        $this->errors = [];
        $this->session = null;
        $this->csrfToken = $this->session()->get('securityCsrfToken');

        $this->templatesDirectory = defined('TEMPLATE_PATH') ? TEMPLATE_PATH : dirname(__DIR__, 4) . '/templates';
        $loader = new FilesystemLoader($this->templatesDirectory);
        $this->templateEngine = new Environment($loader);
    }

    protected function render(string $view, array $vars = []): string
    {
        $sessionVariables = $this->session()->getAll();
        $this->session()->delete('_flashbag');

        return $this->templateEngine->render(
            $view.'.html.twig',
            array_merge($vars, [
                'session'       => $sessionVariables,
                'errors'        => $this->errors,
                'current_page'  => $_SERVER["PATH_INFO"]??'/'
            ])
        );
    }

    protected function isSubmitted(): bool
    {
        if (sizeof($_POST) > 0 && !empty($_POST['csrf_token']) && $_POST['csrf_token'] === $this->csrfToken) {
            return true;
        }

        $this->errors['csrf_token'] = 'Erreur CSRF token !';

        return false;
    }

    protected function generateCsrfToken(): string
    {
        $this->session()->set('securityCsrfToken', sha1(uniqid('csrf_token')));
        $this->csrfToken = $this->session()->get('securityCsrfToken');

        return $this->csrfToken;
    }

    protected function session(): SessionInterface
    {
        if(null === $this->session) {
            $this->session = new Session();
        }
        return $this->session;
    }

    protected function setSession(SessionInterface $sessionHandler): self
    {
        $this->session = $sessionHandler;

        return $this;
    }

    /**
     * Redirect user to the path and finish the execution of the script
     *
     * @param string $url
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    protected function redirectToRoute(string $url): void
    {
        header("Location: {$url}");
        exit();
    }

    protected function addFlash(string $category, string $message): void
    {
        if (null === $this->session()->get('_flashbag')) {
            $this->session()->set('_flashbag',[$category => [$message]]);

            return;
        }
        $this->session()->set(
            '_flashbag',
            array_merge(
                $this->session()->get('_flashbag'),
                [$category => [$message]]
            )
        );
    }

    /**
     * @param string $password
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function encodePassword(string $password): string
    {
        $encodedPassword = password_hash($password, PASSWORD_ARGON2I);

        if (false === $encodedPassword) throw new \Exception('Problem during encoding');

        return $encodedPassword;
    }
}
