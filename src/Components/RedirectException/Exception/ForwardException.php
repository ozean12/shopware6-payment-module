<?php

namespace Billie\BilliePayment\Components\RedirectException\Exception;

class ForwardException extends \Exception
{
    /**
     * @var string
     */
    private $route;

    /**
     * @var array
     */
    private $routeParams;

    /**
     * @var array
     */
    private $queryParams;

    /**
     * ForwardException constructor.
     */
    public function __construct(string $route, array $routeParams = [], array $query = [], \Exception $exception = null)
    {
        parent::__construct(null, null, $exception);
        $this->route = $route;
        $this->routeParams = $routeParams;
        $this->queryParams = $query;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }
}
