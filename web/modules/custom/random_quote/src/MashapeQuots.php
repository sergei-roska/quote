<?php

namespace Drupal\random_quote;

use GuzzleHttp\ClientInterface;
use Drupal\Component\Serialization\Json;

/**
 * Class MashapeQuots.
 */
class MashapeQuots {

  /**
   * Random quotes REST API url.
   */
  const FFW_RANDOM_QUOTE_URL = 'https://api.quotable.io/random';

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new RandomQuoteService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * The method which will return random quote array.
   *
   * @return array
   *   Returns an array with quote details.
   */
  public function getQuote() {
    $response = $this->httpClient->get(MashapeQuots::FFW_RANDOM_QUOTE_URL);
    $response_content = $response->getBody()->getContents();
    $decoded = Json::decode($response_content);

    return $decoded;
  }

}
