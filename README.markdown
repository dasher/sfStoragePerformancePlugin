---
title: sfPerformanceSet
---

Includes:
  sfMongoDBCache by gregoire
  sfMemcachedCache by dasher (Config: http://gist.github.com/232225)

Installing repcached - Memcache + replication
=============================================

$ wget http://downloads.sourceforge.net/repcached/memcached-1.2.6-repcached-2.2.tar.gz
$ tar zxf memcached-1.2.6-repcached-2.2.tar.gz
$ cd memcached-1.2.6-repcached-2.2
--- OR ---
$ wget http://www.danga.com/memcached/dist/memcached-1.2.6.tar.gz
$ tar zxf memcached-1.2.6.tar.gz
$ cd memcached-1.2.6
$ wget http://downloads.sourceforge.net/repcached/repcached-2.2-1.2.6.patch.gz
$ gzip -cd repcached-2.2-1.2.6.patch.gz | patch -p1

$ ./configure --enable-replication
[notice: cannot set both --enable-replication and --enable-thread together]
$ make

make install

Run

repcached adds two new options (-x and -X).

$ memcached -h
(snip)
-x < ip_addr > hostname or IP address of the master replication server
-X < num > TCP port number of the master (default: 11212)

There are two machines called "foo" and "bar".

foo$ ./memcached (start as master)
bar$ ./memcached -x foo (start as slave and connect to master(foo))

Client set key/val to master(foo). We can get same value from slave(bar).

Failover

If master(foo) is down, slave(bar) become the new master automatically.

Failback

start memcached at foo as slave.

foo$ ./memcached -x bar (start as slave and connect to master(bar))

All data which master(bar) has will copy to new slave(foo), then master and slave have same data.