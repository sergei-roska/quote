<?php

namespace Drupal\random_quote\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MashapeBlock' block.
 *
 * @Block(
 *  id = "mashape_block",
 *  admin_label = @Translation("Mashape block"),
 * )
 */
class MashapeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\random_quote\MashapeQuots definition.
   *
   * @var \Drupal\random_quote\MashapeQuots
   */
  protected $randomQuoteMashape;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->randomQuoteMashape = $container->get('random_quote.mashape');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $rand_quote = $this->randomQuoteMashape->getQuote();

    $build['mashape'] = [
      '#type' => 'markup',
      '#title' => $this->t('Random quote'),
      '#open'  => TRUE,
      'inline_holder' => [
        '#type' => 'container',
        '#attributes'  => [
          'class' => 'form--inline clearfix',
        ],
        'author' => [
          '#type' => 'item',
          '#title' => $rand_quote['author'],
          '#plain_text' => $rand_quote['content'],
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
