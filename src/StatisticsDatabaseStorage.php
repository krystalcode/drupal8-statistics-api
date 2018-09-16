<?php

namespace Drupal\statistics_api;

use Drupal\Core\Database\Connection as DatabaseConnection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the default database storage backend for statistics.
 *
 * @todo Update interface.
 */
class StatisticsDatabaseStorage implements StatisticsStorageInterface {

  /**
   * The database connection used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs the statistics storage.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection used.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    DatabaseConnection $database,
    RequestStack $request_stack
  ) {
    $this->database = $database;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(
    $name,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    $query = $this->fetchQuery([
      'names' => [$name],
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'user_id' => $user_id,
    ]);
    $records = $query->execute()->fetchAll();

    if (!$records) {
      return;
    }

    return current($records);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchValue(
    $name,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    $query = $this->fetchQuery([
      'fields' => ['value'],
      'names' => [$name],
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'user_id' => $user_id,
    ]);
    $records = $query->execute()->fetchAll();

    if (!$records) {
      return;
    }

    $record = current($records);
    return $record->value;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchMultiple(
    array $names,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    $query = $this->fetchQuery([
      'names' => $names,
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'user_id' => $user_id,
    ]);
    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   *
   * @todo Provide options in other fetch functions.
   */
  public function fetchMultipleValues(
    array $names,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0,
    array $options = []
  ) {
    $options = array_merge([
      'default_value' => NULL,
      'cast_to_integer' => NULL,
    ], $options);

    $query = $this->fetchQuery([
      'fields' => ['name', 'value'],
      'names' => $names,
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'user_id' => $user_id,
    ]);
    $records = $query->execute()->fetchAll();

    if (!$records && is_null($options['default_value'])) {
      return [];
    }

    $values = new \StdClass();
    foreach ($records as $record) {
      if ($options['cast_to_integer']) {
        $values->{$record->name} = intval($record->value);
      }
      else {
        $values->{$record->name} = $record->value;
      }
    }

    if (!is_null($options['default_value'])) {
      foreach ($names as $name) {
        if (!isset($values->{$name})) {
          $values->{$name} = $options['default_value'];
        }
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll($entity_type = '', $entity_id = 0, $user_id = 0) {
    $query = $this->fetchQuery([
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'user_id' => $user_id,
    ]);
    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllValues(
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    $query = $this->fetchQuery([
      'fields' => ['name', 'value'],
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'user_id' => $user_id,
    ]);
    $records = $query->execute()->fetchAll();

    if (!$records) {
      return [];
    }

    $values = new \StdClass();
    foreach ($records as $record) {
      $values->{$record->name} = $record->value;
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function insert(
    $name,
    $value,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    $this->database
      ->insert('statistics_api')
      ->fields([
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'user_id' => $user_id,
        'name' => $name,
        'value' => $value,
        'changed' => $this->getRequestTime(),
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function update(
    $name,
    $value,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    return $this->database
      ->update('statistics_api')
      ->fields([
        'value' => $value,
        'changed' => $this->getRequestTime(),
      ])
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->condition('user_id', $user_id)
      ->condition('name', $name)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function insertOrUpdate(
    $name,
    $value,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    $exists = $this->fetchValue($name, $entity_type, $entity_id, $user_id);

    if (is_null($exists)) {
      $result = $this->insert($name, $value, $entity_type, $entity_id, $user_id);
    }
    else {
      $result = $this->update($name, $value, $entity_type, $entity_id, $user_id);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function insertIfNotExists(
    $name,
    $value,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    $exists = $this->fetchValue($name, $entity_type, $entity_id, $user_id);

    if (is_null($exists)) {
      return $this->insert($name, $value, $entity_type, $entity_id, $user_id);
    }

    return;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(
    $name,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    return $this->database
      ->delete('statistics_api')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->condition('user_id', $user_id)
      ->condition('name', $name)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function increment(
    $name,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    return (bool) $this->database
      ->merge('statistics_api')
      ->key([
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'user_id' => $user_id,
        'name' => $name,
      ])
      ->fields([
        'value' => 1,
        'changed' => $this->getRequestTime(),
      ])
      ->expression('value', 'value + 1')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function decrement(
    $name,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  ) {
    return (bool) $this->database
      ->merge('statistics_api')
      ->key([
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'user_id' => $user_id,
        'name' => $name,
      ])
      ->fields([
        'value' => 0,
        'changed' => $this->getRequestTime(),
      ])
      ->expression('value', 'value - 1')
      ->execute();
  }

  /**
   * Prepares a fetch query based on the given options.
   *
   * @I Remove the `name` option, only `names` seem to be used
   *
   * priority: normal
   * labels: documentation
   *
   * @param array $options
   *   The options that will define the prepared query. Available options are:
   *   - fields, array: An array of fields to fetch for each record. Available values
   *     are 'entity_type', 'entity_id', 'user_id', 'name', 'value' and
   *     'changed'.
   *   - entity_type, string: The type of the entity that the entry belongs to.
   *   - entity_id, int: The ID of the entity that the entry belongs to.
   *   - user_id, int: The ID of the user that the entry belongs to.
   *   - name, string: The machine name of the entry.
   *   - names, array: An array with the machine name of the entries to
   *     fetch. Either the 'name' or the 'names' option should be provided.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The prepared select query.
   */
  protected function fetchQuery(array $options = []) {
    $options = array_merge([
      'fields' => NULL,
      'entity_type' => '',
      'entity_id' => 0,
      'user_id' => 0,
      'names' => NULL,
    ], $options);

    $query = $this->database->select('statistics_api', 's');

    if ($options['fields']) {
      $query->fields('s', $options['fields']);
    }
    else {
      $query->fields('s');
    }

    $query->condition('entity_type', $options['entity_type']);
    $query->condition('entity_id', $options['entity_id']);
    $query->condition('user_id', $options['user_id']);

    if ($options['names']) {
      if (count($options['names']) == 1) {
        $query->condition('name', current($options['names']));
      }
      else {
        $or_condition = $query->orConditionGroup();
        foreach ($options['names'] as $name) {
          $or_condition->condition('name', $name);
        }
        $query->condition($or_condition);
      }
    }

    return $query;
  }

  /**
   * Get current request time.
   *
   * @return int
   *   Unix timestamp for current server request time.
   */
  protected function getRequestTime() {
    return $this->requestStack->getCurrentRequest()
      ->server
      ->get('REQUEST_TIME');
  }

}
