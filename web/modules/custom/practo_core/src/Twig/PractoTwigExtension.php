<?php

namespace Drupal\practo_core\Twig;

use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PractoTwigExtension extends AbstractExtension {

  public function __construct(
    protected PathValidatorInterface $pathValidator,
    protected RouteProviderInterface $routeProvider,
  ) {}

  public function getFunctions(): array {
    return [
      new TwigFunction('practo_safe_internal', [$this, 'safeInternal']),
      new TwigFunction('practo_safe_route', [$this, 'safeRoute']),
    ];
  }

  public function safeInternal(string $path, array $query = []): string {
    $path = trim($path);
    if ($path === '') {
      return Url::fromRoute('<front>')->toString();
    }

    if ($path[0] !== '/') {
      $path = '/' . $path;
    }

    $url = $this->pathValidator->getUrlIfValid($path);
    if ($url) {
      if (!empty($query)) {
        $url->setOption('query', $query);
      }
      return $url->toString();
    }

    return Url::fromRoute('<front>')->toString();
  }

  public function safeRoute(string $routeName, array $parameters = [], array $options = []): string {
    try {
      // Throws if route does not exist.
      $this->routeProvider->getRouteByName($routeName);
    }
    catch (\Throwable $e) {
      return Url::fromRoute('<front>')->toString();
    }

    try {
      return Url::fromRoute($routeName, $parameters, $options)->toString();
    }
    catch (\Throwable $e) {
      return Url::fromRoute('<front>')->toString();
    }
  }

}
