<?php

namespace FabianGO\Assessment\Controllers;

use FabianGO\Assessment\Factories\UserFactory;
use \Slim\Http\Request;
use \Slim\Http\Response;

class WebController extends BaseController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(Request $request, Response $response, array $args)
    {
        $params = [
            'errors' => [],
        ];

        return $this->view->render($response, 'Web/index.twig', $params);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function processIndex(Request $request, Response $response, array $args)
    {
        /** @var UserFactory $factory */
        $factory = $this->container->get('UserFactory');
        $userInput = $request->getParsedBody();

        $user = $factory->create($userInput);

        if ($user == null) {
            $params = [
                'user_input' => $userInput,
                'errors' => $factory->getErrors()
            ];

            return $this->view->render($response, 'Web/index.twig', $params);
        }

        return $response->withRedirect('/user/'.$user->db_id);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function user(Request $request, Response $response, array $args)
    {
        /** @var UserFactory $factory */
        $factory = $this->container->get('UserFactory');
        $user = $factory->get($args['id']);

        $params = [
            'user' => $user
        ];

        return $this->view->render($response, 'Web/user.twig', $params);
    }
}