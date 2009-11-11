<?php
/* This is free software
 *
 * Copyright 2009 - GrÃ©goire HUBERT <hubert.greg@gmail.com>
 * See the LICENSE file coming with this package
 */

class sfMongoDBCache extends sfCache
{
  protected $connection = null;
  protected $host       = "localhost";
  protected $port       = 27017;
  protected $database   = 'sfCache';
  protected $collection = 'sf_cache';
  protected $coll_db;

  /**
   * Initializes this sfCache instance.
   *
   * Available options:
   *
   * * host:     the hostname of the mongodb server
   * * port:     the TCP port of the mongodb server
   * * database: database name to put the data in
   *
   * * see sfCache for options available for all drivers
   *
   * @see sfCache
   */
  public function initialize($options = array())
  {
    if (!class_exists('Mongo'))
    {
      throw new sfInitializationException(sprintf('the mongodb extension is not installed or enable, cannot use sfMongoDbCache.'));
    }   

    parent::initialize($options);

    if (!$this->hasOption('database'))
    {
      throw new sfInitializationException(sprintf('You must provide a database name to store the cache in'));
    }
    $this->database = $this->getOption('database');

    foreach (array('host', 'port', 'collection') as $option)
    {
      if ($this->hasOption($option))
      {
        $this->$option = $this->getOption($option);
      }
    }

    try
    {
      $this->handler = new mongo($this->host.":".$this->port);
      $this->coll_db = $this->handler->selectDB($this->database)->selectCollection($this->collection);
      $this->coll_db->ensureIndex('_id');
    }
    catch (Exception $e)
    {
      throw new sfInitializationException(sprintf('Could not connect to mongo database. Driver said Â«%sÂ»', $e->getMessage()));
    }
  }

  /**
   * @see sfCache
   */
  public function has($key)
  {
    return $this->coll_db->count(array('_id' => $key, 'timeout' => array('$gt' => time()))) > 0;
  }

  /**
   * retrieve a living record from the database
   */
  protected function getRecord($key)
  {
    $results =  $this->coll_db->find(array('_id' => $key, 'timeout' => array('$gt' => time())));
    if ($results->hasNext())
    {
      $result = $results->getNext();
      return $result;
    }

    return null;
  }

  /**
   * @see sfCache
   */
  public function get($key, $default = null)
  {
    $record = $this->getRecord($key);

    return is_null($record) ? $default : $record['value'];
  }

  /**
   * @see sfCache
   */
  public function set($key, $value, $lifetime = null)
  {
    if ($this->getOption('automatic_cleaning_factor') > 0 && rand(1, $this->getOption('automatic_cleaning_factor')) == 1)
    {
      $this->clean(sfCache::OLD);
    }

    $lifetime = is_null($lifetime) ? $this->getOption('lifetime') : $lifetime;
    $time = time();
    $set = array('_id' => $key, 'value' => $value, 'updated_at' => $time, 'timeout' => $time + $lifetime);
    return $this->coll_db->save($set);
  }

  /**
   * @see sfCache
   */
  public function remove($key)
  {
    $this->coll_db->remove(array('_id' => $key));
  }

  /**
   * @see sfCache
   */
  public function removePattern($pattern)
  {
    $regexp = new MongoRegex(stripslashes(str_replace('#', '/', self::patternToRegexp($pattern))));
    $this->coll_db->remove(array('_id' => $regexp));
  }

  /**
   * @see sfCache
   */
  public function clean($mode = self::ALL)
  {
    if ($mode == self::ALL)
    {
      return $this->coll_db->remove(array());
    }
    elseif ($mode == self::OLD)
    {
      return $this->coll_db->remove(array('timeout' => array("\$lt" < time())));
    }
    else
    {
      throw new sfException(sprintf('Unknown cache clean mode "%s"', $mode));
    }
  }

  /**
   * @see sfCache
   */
  public function getTimeout($key)
  {
    $record = $this->getRecord($key);

    if (is_null($record) or is_null($record['timeout'])) return 0;

    return $record['timeout'];
  }

  /**
   * @see sfCache
   */
  public function getLastModified($key)
  {
    $record = $this->getRecord($key);

    if (!is_null($record))
    {
      return $record['updated_at'];
    }

    return null;
  }

  /**
   * @see sfCache
   */
  public function getMany($keys)
  {
    $records = $this->coll_db->find(array('_id' => array('$in' => $keys)));

    $results = array();
    while ($record = $records->GetNext())
    {
      $results[$record['_id']]= $record['value'];
    }

    return $results;
  }

  /**
   * @see sfCache
   */
  public function getBackend()
  {
    return $this->handler;
  }

  /**
   * @see sfCache
   */
  public function hasOption($option)
  {
    return array_key_exists($option, $this->options);
  }
}
