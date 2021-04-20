<?php

namespace Drupal\article_block\Controller;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class NodeCounterController.
 */
class NodeCounterController extends ControllerBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\article_block\NodeStatisticsService definition.
   *
   * @var \Drupal\article_block\NodeStatisticsService
   */
  protected $nodeStatistics;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->nodeStatistics = $container->get('article_block.node.statistics');
    return $instance;
  }

  /**
   * Record plus one view.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   A command to send the selection to the current field widget.
   */
  public function ajaxCall(Request $request) {
    if (!$request->isXmlHttpRequest()) {
      throw new NotFoundHttpException();
    }

    $this->nodeStatistics->recordView($request->get('nid'));

    return new AjaxResponse();
  }

}
