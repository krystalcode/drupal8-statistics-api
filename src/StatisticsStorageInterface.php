<?php

namespace Drupal\statistics_api;

/**
 * Provides an interface for Statistics Storages.
 *
 * Stores statistical numeric values for entities identified by the specific
 * entity type/ID and user ID combination, together with a machine name. To
 * track less specific entries, the following should be use:
 *
 * - To add a generic value that does not relate to a specific entity or entity
 *   type, set the entity_type to an empty string.
 * - To add a value that relates to an entity type but not a specific entity,
 *   provide the entity type but set the entity ID to zero.
 * - To add a value that does not relate to a specific user, set the user ID to
 *   zero.
 */
interface StatisticsStorageInterface {

  /**
   * Fetches the entry identified by the given parameters.
   *
   * @param string $name
   *   The machine name of the entry.
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   *
   * @return object|null
   *   An stdClass object that contains the entry, or NULL if the entry was not
   *   found.
   */
  public function fetch(
    $name,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

  /**
   * Fetches the value of the entry identified by the given parameters.
   *
   * @param string $name
   *   The machine name of the entry.
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   *
   * @return string|null
   *   The numeric value of the entry as a string, or NULL if the entry was not
   *   found.
   */
  public function fetchValue(
    $name,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

  /**
   * Fetches all entries identified by the given parameters.
   *
   * The purpose of the function is to provide a way for fetching all entries
   * for a specific entity_type/entity_id/user_id combination. It therefore does
   * not accept entry names as a parameter.
   *
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   *
   * @return object[]|null
   *   An array of stdClass object that contain the entries, or NULL if no
   *   entries were found.
   */
  public function fetchAll(
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

  /**
   * Fetches the values for all entries identified by the given parameters.
   *
   * The purpose of the function is to provide a way for fetching the values for
   * all entries for a specific entity_type/entity_id/user_id combination. It
   * therefore does not accept entry names as a parameter.
   *
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   *
   * @return array|null
   *   An array keyed by the machine names of the entries and the values of the
   *   entries as its values, or NULL if no entries were found.
   */
  public function fetchAllValues(
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

  /**
   * Inserts an entry with the given values.
   *
   * Normally, the insert query would return the ID of the inserted entry. Our
   * table though has a combined primary key and the insert query will always
   * return "0", so there's no point in returning anything.
   *
   * @I Identify operational success based on return value
   *
   * priority: low
   * notes:    Investigate whether we can identify if the operation was
   *           successful depending on whether the return value of the query was
   *           0 or NULL.
   *
   * @param string $name
   *   The machine name of the entry.
   * @param int|float $value
   *   The value of the entry.
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   */
  public function insert(
    $name,
    $value,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

  /**
   * Updates an entry with the given value.
   *
   * All parameters apart from 'value' serve as identifiers; only the 'value'
   * column will be updated on the entry.
   *
   * @param string $name
   *   The machine name of the entry.
   * @param int|float $value
   *   The value of the entry.
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   *
   * @return int
   *   The number of rows affected by the operation; would normally be 1 or 0 if
   *   no entry was found that could be updated.
   */
  public function update(
    $name,
    $value,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

  /**
   * Inserts or updates an entry with the given values.
   *
   * If the entry does not exist, it will be created with all give
   * parameters. If the entry exists, its 'value' column will be updated.
   *
   * @param string $name
   *   The machine name of the entry.
   * @param int|float $value
   *   The value of the entry.
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   *
   * @return null|int
   *   NULL if the entry was created, the number of rows affected by the
   *   operation if the entry was update; would normally be 1.
   *
   * @see self::insert()
   * @see self::update()
   */
  public function insertOrUpdate(
    $name,
    $value,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

  /**
   * Inserts an entry with the given values if one does not already exist.
   *
   * @param string $name
   *   The machine name of the entry.
   * @param int|float $value
   *   The value of the entry.
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   */
  public function insertIfNotExists(
    $name,
    $value,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

  /**
   * Deletes the entry identified by the given parameters.
   *
   * @param string $name
   *   The machine name of the entry.
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   *
   * @return int
   *   The number of rows affected by the operation; would normally be 1 or 0 if
   *   no entry was found that could be deleted.
   */
  public function delete(
    $name,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

  /**
   * Increments the value of the entry identified by the given parameters.
   *
   * If the entry does not exist it will be created with a value of 1.
   *
   * @param string $name
   *   The machine name of the entry.
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   *
   * @return bool
   *   TRUE if the operation was successful.
   */
  public function increment(
    $name,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

  /**
   * Decrements the value of the entry identified by the given parameters.
   *
   * If the entry does not exist it will be created with a value of 0.
   *
   * @param string $name
   *   The machine name of the entry.
   * @param string $entity_type
   *   The type of the entity that the entry belongs to.
   * @param int $entity_id
   *   The ID of the entity that the entry belongs to.
   * @param int $user_id
   *   The ID of the user that the entry belongs to.
   *
   * @return bool
   *   TRUE if the operation was successful.
   */
  public function decrement(
    $name,
    $entity_type = '',
    $entity_id = 0,
    $user_id = 0
  );

}
