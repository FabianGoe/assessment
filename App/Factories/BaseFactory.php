<?php

namespace FabianGO\Assessment\Factories;

abstract class BaseFactory
{
    /** @var array */
    protected $settings = [];

    /** @var null|\PDO */
    protected $pdo = null;

    /** @var array */
    protected $errors = [];

    /**
     * @param \Slim\Container $container
     */
    public function __construct($container)
    {
    $this->settings = $container->get('settings');
    $this->pdo = $container->get('pdo');
    }

    /**
     * Required create method
     *
     * @param array $params
     */
    abstract function create(array $params);

    /**
     * Required get method
     *
     * @param int $id
     */
    abstract function get($id);

    /**
     * Returns array with errors
     *
     * @return array
     */
    abstract function getErrors();
}