## sfPerformanceSet ##

* Includes:
  * sfMongoDBCache by gregoire
  * sfMemcachedCache by dasher (Config: http://gist.github.com/232225)

### Downloading repcached  source - Memcache + replication ###

$ wget [repcached complete](http://downloads.sourceforge.net/repcached/memcached-1.2.6-repcached-2.2.tar.gz)

$ tar zxf memcached-1.2.6-repcached-2.2.tar.gz

$ cd memcached-1.2.6-repcached-2.2

--- OR ---

$ wget [memcache source](http://www.danga.com/memcached/dist/memcached-1.2.6.tar.gz)

$ tar zxf memcached-1.2.6.tar.gz

$ cd memcached-1.2.6

$ wget [repcached patch](http://downloads.sourceforge.net/repcached/repcached-2.2-1.2.6.patch.gz)

$ gzip -cd repcached-2.2-1.2.6.patch.gz | patch -p1


### Installing ###

$ ./configure --enable-replication // [notice: cannot set both --enable-replication and --enable-thread together]

$ make

$ make install


## Running ##

repcached adds two new options (-x and -X).


There are two machines called "foo" and "bar".

foo$ ./memcached (start as master)

bar$ ./memcached -x foo (start as slave and connect to master(foo))

Client set key/val to master(foo). We can get same value from slave(bar).

### Failover ###

If master(foo) is down, slave(bar) become the new master automatically.

### Failback ###

start memcached at foo as slave.

foo$ ./memcached -x bar (start as slave and connect to master(bar))

All data which master(bar) has will copy to new slave(foo), then master and slave have same data.

## Symfony Integration - for repcached##

### factories.yml ###



    all:
      storage:
        class: sfCacheSessionStorage
        param:
          session_name:             session_identifier
          session_cookie_domain:    .session_identifier.local
          session_cookie_lifetime:  +30 days
          session_cookie_secret:    s0m3th1ngS3cr3t
          cache:
            class: sfMemcachedCache
            param:
              lifetime: 7200
              prefix: somePrefix
              servers:
                server1:
                  host: one.local
                  port: 11211
                server2:
                  host: two.local
                  port: 11211
