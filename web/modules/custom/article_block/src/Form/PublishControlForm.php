<?php

namespace Drupal\article_block\Form;

use Drupal\node\NodeInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PublishControlForm.
 */
class PublishControlForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->nodeStatistics = $container->get('article_block.node.statistics');

    return $instance;
  }

  /**
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Node Storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function nodeStorage() {
    return $this->entityTypeManager->getStorage('node');
  }

  /**
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   User Storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function userStorage() {
    return $this->entityTypeManager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'article_block.publish_control',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'publish_control_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $views_today = 0;
    $views_total = 0;
    $account_name = '';
    $date = '';
    if (!empty($form_state->getValue('title'))) {
      /** @var \Drupal\node\NodeInterface $selected_node */
      $selected_node = $this->nodeStorage()->load($form_state->getValue('title'));
      $form_state->setValue('status', intval($selected_node->isPublished()));
      $form_state->setValue('sticky', intval($selected_node->isSticky()));

      $statistics = $this->nodeStatistics->fetchView($selected_node->id());
      $views_today = $statistics['views_today'] ?: 0;
      $views_total = $statistics['views_total'] ?: 0;
      $account_name = $statistics['user'] ? $this->userStorage()
        ->load($statistics['user'])
        ->getAccountName() : '';
      $date = $statistics['timestamp'] ? date('d/m/Y', $statistics['timestamp']) : '';
    }

    $nodes = $this->nodeStorage()->loadByProperties(['type' => 'article']);

    $nodes = array_map([$this, 'getNodeTitle'], $nodes);
    ksort($nodes);

    $form['#prefix'] = '<div id="node-articles">';
    $form['#suffix'] = '</div>';

    $form['title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#description' => $this->t('List of all nodes on type article'),
      '#empty_option' => t('- Select node -'),
      '#options' => $nodes,
      '#default_value' => $form_state->getValue('title', 0),
      '#size' => 1,
      '#ajax' => [
        'callback' => [$this, 'nodeSelected'],
        'wrapper' => 'node-articles',
        'event' => 'change',
      ],
    ];

    $form['statistics'] = [
      '#type' => 'markup',
      '#title' => $this->t('Statistics'),
      '#open'  => TRUE,
      'inline_holder' => [
        '#type' => 'container',
        '#states' => [
          'invisible' => [
            ':input[name=title]' => ['value' => ''],
          ],
        ],
        '#attributes'  => [
          'class' => 'form--inline clearfix',
        ],
        'views_today' => [
          '#type' => 'item',
          '#title' => $this->t('Number of views today.'),
          '#plain_text' => $views_today,
        ],
        'views_total' => [
          '#type' => 'item',
          '#title' => $this->t('Number of views total.'),
          '#plain_text' => $views_total,
        ],
        'user' =>[
          '#type' => 'item',
          '#title' => $this->t('Last viewed username'),
          '#plain_text' => $account_name,
        ],
        'timestamp' =>[
          '#type' => 'item',
          '#title' => $this->t('Date'),
          '#plain_text' => $date,
        ],
      ],
    ];

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#description' => $this->t('Status: publish, not publish'),
      '#options' => [$this->t('Not published'), $this->t('Published')],
      '#size' => 1,
      '#states' => [
        'invisible' => [
        ':input[name=title]' => ['value' => ''],
        ],
      ],
      '#value' => $form_state->getValue('status', 0),
    ];

    $form['sticky'] = [
      '#type' => 'select',
      '#title' => $this->t('Sticky'),
      '#options' => [$this->t('Not sticky'), $this->t('Sticky')],
      '#size' => 1,
      '#states' => [
        'invisible' => [
          ':input[name=title]' => ['value' => ''],
        ],
      ],
      '#value' => $form_state->getValue('sticky', 0),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['edit_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save node'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => [$this, 'submitForm'],
        'wrapper' => 'node-articles',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['actions']['delete_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete node'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => [$this, 'deleteForm'],
        'wrapper' => 'node-articles',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Save edited node.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form elements.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('title'))) {
      return $form;
    }

    /** @var \Drupal\node\NodeInterface $selected_node */
    $selected_node = $this->nodeStorage()->load($form_state->getValue('title'));
    if (!$selected_node) {
      return $form;
    }

    $selected_node->setPublished($form_state->getValue('status'));
    $selected_node->setSticky($form_state->getValue('sticky'));
    $selected_node->save();

    return $form;
  }

  /**
   * Delete edited node.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form elements.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('title'))) {
      return $form;
    }

    /** @var \Drupal\node\NodeInterface $selected_node */
    $selected_node = $this->nodeStorage()->load($form_state->getValue('title'));
    if (!$selected_node) {
      return $form;
    }

    $selected_node->delete();
    unset($form['title']['#options'][$selected_node->id()]);

    return $form;
  }

  /**
   * @param \Drupal\node\NodeInterface $node
   *
   * @return string
   *   Node title.
   */
  private function getNodeTitle(NodeInterface $node) {
    return $node->getTitle();
  }

  /**
   * AJAX form submit callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. If the user submitted the form by clicking
   *   a button with custom handler functions defined, those handlers will be
   *   stored here.
   *
   * @return array
   *   Form array.
   */
  public function nodeSelected(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
