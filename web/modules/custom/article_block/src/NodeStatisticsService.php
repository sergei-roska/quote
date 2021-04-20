<?php

namespace Drupal\article_block;

use Drupal\Core\State\StateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\statistics\StatisticsViewsResult;
use Drupal\Core\Database\Driver\mysql\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class NodeStatisticsService.
 */
class NodeStatisticsService {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new NodeStatisticsService object.
   */
  public function __construct(Connection $database, StateInterface $state, RequestStack $request_stack, AccountInterface $current_user) {
    $this->state = $state;
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * Get current request time.
   *
   * @return int
   *   Unix timestamp for current server request time.
   */
  protected function getRequestTime() {
    return $this->requestStack->getCurrentRequest()->server->get('REQUEST_TIME');
  }

  public function resetDayCount() {
    $statistics_timestamp = $this->state->get('article_block.day_timestamp') ?: 0;
    if (($this->getRequestTime() - $statistics_timestamp) >= 86400) {
      $this->state->set('article_block.day_timestamp', $this->getRequestTime());
      $this->database->update('article_node_counter')
        ->fields(['daycount' => 0])
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function recordView($id) {
    return (bool) $this->database
      ->merge('article_node_counter')
      ->key('nid', $id)
      ->fields([
        'daycount' => 1,
        'totalcount' => 1,
        'timestamp' => $this->getRequestTime(),
        'user' => $this->currentUser->id(),
      ])
      ->expression('daycount', 'daycount + 1')
      ->expression('totalcount', 'totalcount + 1')
      ->execute();
  }

  public function fetchViews($ids) {
    $views = $this->database
      ->select('article_node_counter', 'anc')
      ->fields('anc', ['totalcount', 'daycount', 'timestamp', 'user'])
      ->condition('nid', $ids, 'IN')
      ->execute()
      ->fetchAll();

    foreach ($views as $id => $view) {
      $views[$id] = [
        'views_total' => $view->totalcount,
        'views_today' => $view->daycount,
        'timestamp' => $view->timestamp,
        'user' => $view->user,
      ];
    }

    return $views;
  }

  public function fetchView($id) {
    $views = $this->fetchViews([$id]);

    return reset($views);
  }

}
