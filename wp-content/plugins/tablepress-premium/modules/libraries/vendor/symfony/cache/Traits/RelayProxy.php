<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TablePress\Symfony\Component\Cache\Traits;

use TablePress\Symfony\Component\Cache\Traits\Relay\CopyTrait;
use TablePress\Symfony\Component\Cache\Traits\Relay\GeosearchTrait;
use TablePress\Symfony\Component\Cache\Traits\Relay\GetrangeTrait;
use TablePress\Symfony\Component\Cache\Traits\Relay\HsetTrait;
use TablePress\Symfony\Component\Cache\Traits\Relay\MoveTrait;
use TablePress\Symfony\Component\Cache\Traits\Relay\NullableReturnTrait;
use TablePress\Symfony\Component\Cache\Traits\Relay\PfcountTrait;
use TablePress\Symfony\Component\VarExporter\LazyObjectInterface;
use TablePress\Symfony\Contracts\Service\ResetInterface;

// Help opcache.preload discover always-needed symbols
class_exists(\TablePress\Symfony\Component\VarExporter\Internal\Hydrator::class);
class_exists(\TablePress\Symfony\Component\VarExporter\Internal\LazyObjectRegistry::class);
class_exists(\TablePress\Symfony\Component\VarExporter\Internal\LazyObjectState::class);

/**
 * @internal
 */
class RelayProxy extends \Relay\Relay implements ResetInterface, LazyObjectInterface
{
	use CopyTrait;
	use GeosearchTrait;
	use GetrangeTrait;
	use HsetTrait;
	use MoveTrait;
	use NullableReturnTrait;
	use PfcountTrait;
	use RedisProxyTrait {
		resetLazyObject as reset;
	}
	use RelayProxyTrait;

	public function __construct($host = null, $port = 6379, $connect_timeout = 0.0, $command_timeout = 0.0, $context = [], $database = 0)
	{
		$this->initializeLazyObject()->__construct(...\func_get_args());
	}

	public function connect($host, $port = 6379, $timeout = 0.0, $persistent_id = null, $retry_interval = 0, $read_timeout = 0.0, $context = [], $database = 0): bool
	{
		return $this->initializeLazyObject()->connect(...\func_get_args());
	}

	public function pconnect($host, $port = 6379, $timeout = 0.0, $persistent_id = null, $retry_interval = 0, $read_timeout = 0.0, $context = [], $database = 0): bool
	{
		return $this->initializeLazyObject()->pconnect(...\func_get_args());
	}

	public function close(): bool
	{
		return $this->initializeLazyObject()->close(...\func_get_args());
	}

	public function pclose(): bool
	{
		return $this->initializeLazyObject()->pclose(...\func_get_args());
	}

	public function listen($callback): bool
	{
		return $this->initializeLazyObject()->listen(...\func_get_args());
	}

	public function onFlushed($callback): bool
	{
		return $this->initializeLazyObject()->onFlushed(...\func_get_args());
	}

	public function onInvalidated($callback, $pattern = null): bool
	{
		return $this->initializeLazyObject()->onInvalidated(...\func_get_args());
	}

	/**
	 * @return int|false
	 */
	public function dispatchEvents()
	{
		return $this->initializeLazyObject()->dispatchEvents(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function getOption($option)
	{
		return $this->initializeLazyObject()->getOption(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function option($option, $value = null)
	{
		return $this->initializeLazyObject()->option(...\func_get_args());
	}

	public function setOption($option, $value): bool
	{
		return $this->initializeLazyObject()->setOption(...\func_get_args());
	}

	public function addIgnorePatterns(...$pattern): int
	{
		return $this->initializeLazyObject()->addIgnorePatterns(...\func_get_args());
	}

	public function addAllowPatterns(...$pattern): int
	{
		return $this->initializeLazyObject()->addAllowPatterns(...\func_get_args());
	}

	/**
	 * @return float|false
	 */
	public function getTimeout()
	{
		return $this->initializeLazyObject()->getTimeout(...\func_get_args());
	}

	/**
	 * @return float|false
	 */
	public function timeout()
	{
		return $this->initializeLazyObject()->timeout(...\func_get_args());
	}

	/**
	 * @return float|false
	 */
	public function getReadTimeout()
	{
		return $this->initializeLazyObject()->getReadTimeout(...\func_get_args());
	}

	/**
	 * @return float|false
	 */
	public function readTimeout()
	{
		return $this->initializeLazyObject()->readTimeout(...\func_get_args());
	}

	public function getBytes(): array
	{
		return $this->initializeLazyObject()->getBytes(...\func_get_args());
	}

	public function bytes(): array
	{
		return $this->initializeLazyObject()->bytes(...\func_get_args());
	}

	/**
	 * @return string|false
	 */
	public function getHost()
	{
		return $this->initializeLazyObject()->getHost(...\func_get_args());
	}

	public function isConnected(): bool
	{
		return $this->initializeLazyObject()->isConnected(...\func_get_args());
	}

	/**
	 * @return int|false
	 */
	public function getPort()
	{
		return $this->initializeLazyObject()->getPort(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function getAuth()
	{
		return $this->initializeLazyObject()->getAuth(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function getDbNum()
	{
		return $this->initializeLazyObject()->getDbNum(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function _serialize($value)
	{
		return $this->initializeLazyObject()->_serialize(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function _unserialize($value)
	{
		return $this->initializeLazyObject()->_unserialize(...\func_get_args());
	}

	public function _compress($value): string
	{
		return $this->initializeLazyObject()->_compress(...\func_get_args());
	}

	public function _uncompress($value): string
	{
		return $this->initializeLazyObject()->_uncompress(...\func_get_args());
	}

	public function _pack($value): string
	{
		return $this->initializeLazyObject()->_pack(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function _unpack($value)
	{
		return $this->initializeLazyObject()->_unpack(...\func_get_args());
	}

	public function _prefix($value): string
	{
		return $this->initializeLazyObject()->_prefix(...\func_get_args());
	}

	public function getLastError(): ?string
	{
		return $this->initializeLazyObject()->getLastError(...\func_get_args());
	}

	public function clearLastError(): bool
	{
		return $this->initializeLazyObject()->clearLastError(...\func_get_args());
	}

	/**
	 * @return string|false
	 */
	public function endpointId()
	{
		return $this->initializeLazyObject()->endpointId(...\func_get_args());
	}

	/**
	 * @return string|false
	 */
	public function getPersistentID()
	{
		return $this->initializeLazyObject()->getPersistentID(...\func_get_args());
	}

	/**
	 * @return string|false
	 */
	public function socketId()
	{
		return $this->initializeLazyObject()->socketId(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function rawCommand($cmd, ...$args)
	{
		return $this->initializeLazyObject()->rawCommand(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function select($db)
	{
		return $this->initializeLazyObject()->select(...\func_get_args());
	}

	public function auth($auth): bool
	{
		return $this->initializeLazyObject()->auth(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function info(...$sections)
	{
		return $this->initializeLazyObject()->info(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function flushdb($sync = null)
	{
		return $this->initializeLazyObject()->flushdb(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function flushall($sync = null)
	{
		return $this->initializeLazyObject()->flushall(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function fcall($name, $keys = [], $argv = [], $handler = null)
	{
		return $this->initializeLazyObject()->fcall(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function fcall_ro($name, $keys = [], $argv = [], $handler = null)
	{
		return $this->initializeLazyObject()->fcall_ro(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function function($op, ...$args)
	{
		return $this->initializeLazyObject()->function(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function dbsize()
	{
		return $this->initializeLazyObject()->dbsize(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function replicaof($host = null, $port = 0)
	{
		return $this->initializeLazyObject()->replicaof(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function waitaof($numlocal, $numremote, $timeout)
	{
		return $this->initializeLazyObject()->waitaof(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function restore($key, $ttl, $value, $options = null)
	{
		return $this->initializeLazyObject()->restore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function migrate($host, $port, $key, $dstdb, $timeout, $copy = false, $replace = false, $credentials = null)
	{
		return $this->initializeLazyObject()->migrate(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool|string
	 */
	public function echo($arg)
	{
		return $this->initializeLazyObject()->echo(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool|string
	 */
	public function ping($arg = null)
	{
		return $this->initializeLazyObject()->ping(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function idleTime()
	{
		return $this->initializeLazyObject()->idleTime(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool|null|string
	 */
	public function randomkey()
	{
		return $this->initializeLazyObject()->randomkey(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function time()
	{
		return $this->initializeLazyObject()->time(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function bgrewriteaof()
	{
		return $this->initializeLazyObject()->bgrewriteaof(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function lastsave()
	{
		return $this->initializeLazyObject()->lastsave(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function lcs($key1, $key2, $options = null)
	{
		return $this->initializeLazyObject()->lcs(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function bgsave($schedule = false)
	{
		return $this->initializeLazyObject()->bgsave(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function save()
	{
		return $this->initializeLazyObject()->save(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function role()
	{
		return $this->initializeLazyObject()->role(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function ttl($key)
	{
		return $this->initializeLazyObject()->ttl(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function pttl($key)
	{
		return $this->initializeLazyObject()->pttl(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool|int
	 */
	public function exists(...$keys)
	{
		return $this->initializeLazyObject()->exists(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function eval($script, $args = [], $num_keys = 0)
	{
		return $this->initializeLazyObject()->eval(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function eval_ro($script, $args = [], $num_keys = 0)
	{
		return $this->initializeLazyObject()->eval_ro(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function evalsha($sha, $args = [], $num_keys = 0)
	{
		return $this->initializeLazyObject()->evalsha(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function evalsha_ro($sha, $args = [], $num_keys = 0)
	{
		return $this->initializeLazyObject()->evalsha_ro(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function client($operation, ...$args)
	{
		return $this->initializeLazyObject()->client(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function geoadd($key, $lng, $lat, $member, ...$other_triples_and_options)
	{
		return $this->initializeLazyObject()->geoadd(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function geohash($key, $member, ...$other_members)
	{
		return $this->initializeLazyObject()->geohash(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function georadius($key, $lng, $lat, $radius, $unit, $options = [])
	{
		return $this->initializeLazyObject()->georadius(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function georadiusbymember($key, $member, $radius, $unit, $options = [])
	{
		return $this->initializeLazyObject()->georadiusbymember(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function georadiusbymember_ro($key, $member, $radius, $unit, $options = [])
	{
		return $this->initializeLazyObject()->georadiusbymember_ro(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function georadius_ro($key, $lng, $lat, $radius, $unit, $options = [])
	{
		return $this->initializeLazyObject()->georadius_ro(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function geosearchstore($dst, $src, $position, $shape, $unit, $options = [])
	{
		return $this->initializeLazyObject()->geosearchstore(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function get($key)
	{
		return $this->initializeLazyObject()->get(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function getset($key, $value)
	{
		return $this->initializeLazyObject()->getset(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function setrange($key, $start, $value)
	{
		return $this->initializeLazyObject()->setrange(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function getbit($key, $pos)
	{
		return $this->initializeLazyObject()->getbit(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function bitcount($key, $start = 0, $end = -1, $by_bit = false)
	{
		return $this->initializeLazyObject()->bitcount(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function bitfield($key, ...$args)
	{
		return $this->initializeLazyObject()->bitfield(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|bool
	 */
	public function config($operation, $key = null, $value = null)
	{
		return $this->initializeLazyObject()->config(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|int
	 */
	public function command(...$args)
	{
		return $this->initializeLazyObject()->command(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function bitop($operation, $dstkey, $srckey, ...$other_keys)
	{
		return $this->initializeLazyObject()->bitop(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function bitpos($key, $bit, $start = null, $end = null, $bybit = false)
	{
		return $this->initializeLazyObject()->bitpos(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function setbit($key, $pos, $val)
	{
		return $this->initializeLazyObject()->setbit(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function acl($cmd, ...$args)
	{
		return $this->initializeLazyObject()->acl(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function append($key, $value)
	{
		return $this->initializeLazyObject()->append(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function set($key, $value, $options = null)
	{
		return $this->initializeLazyObject()->set(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function getex($key, $options = null)
	{
		return $this->initializeLazyObject()->getex(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function getdel($key)
	{
		return $this->initializeLazyObject()->getdel(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function setex($key, $seconds, $value)
	{
		return $this->initializeLazyObject()->setex(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function pfadd($key, $elements)
	{
		return $this->initializeLazyObject()->pfadd(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function pfmerge($dst, $srckeys)
	{
		return $this->initializeLazyObject()->pfmerge(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function psetex($key, $milliseconds, $value)
	{
		return $this->initializeLazyObject()->psetex(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function publish($channel, $message)
	{
		return $this->initializeLazyObject()->publish(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function pubsub($operation, ...$args)
	{
		return $this->initializeLazyObject()->pubsub(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function spublish($channel, $message)
	{
		return $this->initializeLazyObject()->spublish(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function setnx($key, $value)
	{
		return $this->initializeLazyObject()->setnx(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function mget($keys)
	{
		return $this->initializeLazyObject()->mget(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function move($key, $db)
	{
		return $this->initializeLazyObject()->move(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function mset($kvals)
	{
		return $this->initializeLazyObject()->mset(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function msetnx($kvals)
	{
		return $this->initializeLazyObject()->msetnx(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function rename($key, $newkey)
	{
		return $this->initializeLazyObject()->rename(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function renamenx($key, $newkey)
	{
		return $this->initializeLazyObject()->renamenx(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool|int
	 */
	public function del(...$keys)
	{
		return $this->initializeLazyObject()->del(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function unlink(...$keys)
	{
		return $this->initializeLazyObject()->unlink(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function expire($key, $seconds, $mode = null)
	{
		return $this->initializeLazyObject()->expire(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function pexpire($key, $milliseconds)
	{
		return $this->initializeLazyObject()->pexpire(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function expireat($key, $timestamp)
	{
		return $this->initializeLazyObject()->expireat(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function expiretime($key)
	{
		return $this->initializeLazyObject()->expiretime(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function pexpireat($key, $timestamp_ms)
	{
		return $this->initializeLazyObject()->pexpireat(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function pexpiretime($key)
	{
		return $this->initializeLazyObject()->pexpiretime(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function persist($key)
	{
		return $this->initializeLazyObject()->persist(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool|int|string
	 */
	public function type($key)
	{
		return $this->initializeLazyObject()->type(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function lrange($key, $start, $stop)
	{
		return $this->initializeLazyObject()->lrange(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function lpush($key, $mem, ...$mems)
	{
		return $this->initializeLazyObject()->lpush(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function rpush($key, $mem, ...$mems)
	{
		return $this->initializeLazyObject()->rpush(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function lpushx($key, $mem, ...$mems)
	{
		return $this->initializeLazyObject()->lpushx(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function rpushx($key, $mem, ...$mems)
	{
		return $this->initializeLazyObject()->rpushx(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function lset($key, $index, $mem)
	{
		return $this->initializeLazyObject()->lset(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function lpop($key, $count = 1)
	{
		return $this->initializeLazyObject()->lpop(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|int|null
	 */
	public function lpos($key, $value, $options = null)
	{
		return $this->initializeLazyObject()->lpos(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function rpop($key, $count = 1)
	{
		return $this->initializeLazyObject()->rpop(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function rpoplpush($source, $dest)
	{
		return $this->initializeLazyObject()->rpoplpush(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function brpoplpush($source, $dest, $timeout)
	{
		return $this->initializeLazyObject()->brpoplpush(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|null
	 */
	public function blpop($key, $timeout_or_key, ...$extra_args)
	{
		return $this->initializeLazyObject()->blpop(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|null
	 */
	public function blmpop($timeout, $keys, $from, $count = 1)
	{
		return $this->initializeLazyObject()->blmpop(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|null
	 */
	public function bzmpop($timeout, $keys, $from, $count = 1)
	{
		return $this->initializeLazyObject()->bzmpop(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|null
	 */
	public function lmpop($keys, $from, $count = 1)
	{
		return $this->initializeLazyObject()->lmpop(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|null
	 */
	public function zmpop($keys, $from, $count = 1)
	{
		return $this->initializeLazyObject()->zmpop(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|null
	 */
	public function brpop($key, $timeout_or_key, ...$extra_args)
	{
		return $this->initializeLazyObject()->brpop(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|null
	 */
	public function bzpopmax($key, $timeout_or_key, ...$extra_args)
	{
		return $this->initializeLazyObject()->bzpopmax(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|null
	 */
	public function bzpopmin($key, $timeout_or_key, ...$extra_args)
	{
		return $this->initializeLazyObject()->bzpopmin(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function object($op, $key)
	{
		return $this->initializeLazyObject()->object(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function geopos($key, ...$members)
	{
		return $this->initializeLazyObject()->geopos(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function lrem($key, $mem, $count = 0)
	{
		return $this->initializeLazyObject()->lrem(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function lindex($key, $index)
	{
		return $this->initializeLazyObject()->lindex(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function linsert($key, $op, $pivot, $element)
	{
		return $this->initializeLazyObject()->linsert(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function ltrim($key, $start, $end)
	{
		return $this->initializeLazyObject()->ltrim(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function hget($hash, $member)
	{
		return $this->initializeLazyObject()->hget(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function hstrlen($hash, $member)
	{
		return $this->initializeLazyObject()->hstrlen(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function hgetall($hash)
	{
		return $this->initializeLazyObject()->hgetall(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function hkeys($hash)
	{
		return $this->initializeLazyObject()->hkeys(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function hvals($hash)
	{
		return $this->initializeLazyObject()->hvals(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function hmget($hash, $members)
	{
		return $this->initializeLazyObject()->hmget(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function hmset($hash, $members)
	{
		return $this->initializeLazyObject()->hmset(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function hexists($hash, $member)
	{
		return $this->initializeLazyObject()->hexists(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function hsetnx($hash, $member, $value)
	{
		return $this->initializeLazyObject()->hsetnx(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function hdel($key, $mem, ...$mems)
	{
		return $this->initializeLazyObject()->hdel(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function hincrby($key, $mem, $value)
	{
		return $this->initializeLazyObject()->hincrby(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool|float
	 */
	public function hincrbyfloat($key, $mem, $value)
	{
		return $this->initializeLazyObject()->hincrbyfloat(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function incr($key, $by = 1)
	{
		return $this->initializeLazyObject()->incr(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function decr($key, $by = 1)
	{
		return $this->initializeLazyObject()->decr(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function incrby($key, $value)
	{
		return $this->initializeLazyObject()->incrby(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function decrby($key, $value)
	{
		return $this->initializeLazyObject()->decrby(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|float
	 */
	public function incrbyfloat($key, $value)
	{
		return $this->initializeLazyObject()->incrbyfloat(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function sdiff($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sdiff(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function sdiffstore($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sdiffstore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function sinter($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sinter(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function sintercard($keys, $limit = -1)
	{
		return $this->initializeLazyObject()->sintercard(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function sinterstore($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sinterstore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function sunion($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sunion(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function sunionstore($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sunionstore(...\func_get_args());
	}

	public function subscribe($channels, $callback): bool
	{
		return $this->initializeLazyObject()->subscribe(...\func_get_args());
	}

	public function unsubscribe($channels = []): bool
	{
		return $this->initializeLazyObject()->unsubscribe(...\func_get_args());
	}

	public function psubscribe($patterns, $callback): bool
	{
		return $this->initializeLazyObject()->psubscribe(...\func_get_args());
	}

	public function punsubscribe($patterns = []): bool
	{
		return $this->initializeLazyObject()->punsubscribe(...\func_get_args());
	}

	public function ssubscribe($channels, $callback): bool
	{
		return $this->initializeLazyObject()->ssubscribe(...\func_get_args());
	}

	public function sunsubscribe($channels = []): bool
	{
		return $this->initializeLazyObject()->sunsubscribe(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function touch($key_or_array, ...$more_keys)
	{
		return $this->initializeLazyObject()->touch(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function pipeline()
	{
		return $this->initializeLazyObject()->pipeline(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function multi($mode = 0)
	{
		return $this->initializeLazyObject()->multi(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|bool
	 */
	public function exec()
	{
		return $this->initializeLazyObject()->exec(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function wait($replicas, $timeout)
	{
		return $this->initializeLazyObject()->wait(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function watch($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->watch(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function unwatch()
	{
		return $this->initializeLazyObject()->unwatch(...\func_get_args());
	}

	public function discard(): bool
	{
		return $this->initializeLazyObject()->discard(...\func_get_args());
	}

	public function getMode($masked = false): int
	{
		return $this->initializeLazyObject()->getMode(...\func_get_args());
	}

	public function clearBytes(): void
	{
		$this->initializeLazyObject()->clearBytes(...\func_get_args());
	}

	/**
	 * @return mixed[]|false
	 */
	public function scan(&$iterator, $match = null, $count = 0, $type = null)
	{
		return $this->initializeLazyObject()->scan($iterator, ...\array_slice(\func_get_args(), 1));
	}

	/**
	 * @return mixed[]|false
	 */
	public function hscan($key, &$iterator, $match = null, $count = 0)
	{
		return $this->initializeLazyObject()->hscan($key, $iterator, ...\array_slice(\func_get_args(), 2));
	}

	/**
	 * @return mixed[]|false
	 */
	public function sscan($key, &$iterator, $match = null, $count = 0)
	{
		return $this->initializeLazyObject()->sscan($key, $iterator, ...\array_slice(\func_get_args(), 2));
	}

	/**
	 * @return mixed[]|false
	 */
	public function zscan($key, &$iterator, $match = null, $count = 0)
	{
		return $this->initializeLazyObject()->zscan($key, $iterator, ...\array_slice(\func_get_args(), 2));
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function keys($pattern)
	{
		return $this->initializeLazyObject()->keys(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|bool|int
	 */
	public function slowlog($operation, ...$extra_args)
	{
		return $this->initializeLazyObject()->slowlog(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function smembers($set)
	{
		return $this->initializeLazyObject()->smembers(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function sismember($set, $member)
	{
		return $this->initializeLazyObject()->sismember(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function smismember($set, ...$members)
	{
		return $this->initializeLazyObject()->smismember(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function srem($set, $member, ...$members)
	{
		return $this->initializeLazyObject()->srem(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function sadd($set, $member, ...$members)
	{
		return $this->initializeLazyObject()->sadd(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false|int
	 */
	public function sort($key, $options = [])
	{
		return $this->initializeLazyObject()->sort(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function sort_ro($key, $options = [])
	{
		return $this->initializeLazyObject()->sort_ro(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|bool
	 */
	public function smove($srcset, $dstset, $member)
	{
		return $this->initializeLazyObject()->smove(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function spop($set, $count = 1)
	{
		return $this->initializeLazyObject()->spop(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function srandmember($set, $count = 1)
	{
		return $this->initializeLazyObject()->srandmember(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function scard($key)
	{
		return $this->initializeLazyObject()->scard(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function script($command, ...$args)
	{
		return $this->initializeLazyObject()->script(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function strlen($key)
	{
		return $this->initializeLazyObject()->strlen(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function hlen($key)
	{
		return $this->initializeLazyObject()->hlen(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function llen($key)
	{
		return $this->initializeLazyObject()->llen(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function xack($key, $group, $ids)
	{
		return $this->initializeLazyObject()->xack(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|bool
	 */
	public function xclaim($key, $group, $consumer, $min_idle, $ids, $options)
	{
		return $this->initializeLazyObject()->xclaim(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|bool
	 */
	public function xautoclaim($key, $group, $consumer, $min_idle, $start, $count = -1, $justid = false)
	{
		return $this->initializeLazyObject()->xautoclaim(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function xlen($key)
	{
		return $this->initializeLazyObject()->xlen(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function xgroup($operation, $key = null, $group = null, $id_or_consumer = null, $mkstream = false, $entries_read = -2)
	{
		return $this->initializeLazyObject()->xgroup(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function xdel($key, $ids)
	{
		return $this->initializeLazyObject()->xdel(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function xinfo($operation, $arg1 = null, $arg2 = null, $count = -1)
	{
		return $this->initializeLazyObject()->xinfo(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function xpending($key, $group, $start = null, $end = null, $count = -1, $consumer = null, $idle = 0)
	{
		return $this->initializeLazyObject()->xpending(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function xrange($key, $start, $end, $count = -1)
	{
		return $this->initializeLazyObject()->xrange(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|bool
	 */
	public function xrevrange($key, $end, $start, $count = -1)
	{
		return $this->initializeLazyObject()->xrevrange(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|bool|null
	 */
	public function xread($streams, $count = -1, $block = -1)
	{
		return $this->initializeLazyObject()->xread(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|bool|null
	 */
	public function xreadgroup($group, $consumer, $streams, $count = 1, $block = 1)
	{
		return $this->initializeLazyObject()->xreadgroup(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function xtrim($key, $threshold, $approx = false, $minid = false, $limit = -1)
	{
		return $this->initializeLazyObject()->xtrim(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function zadd($key, ...$args)
	{
		return $this->initializeLazyObject()->zadd(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function zrandmember($key, $options = null)
	{
		return $this->initializeLazyObject()->zrandmember(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zrange($key, $start, $end, $options = null)
	{
		return $this->initializeLazyObject()->zrange(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zrevrange($key, $start, $end, $options = null)
	{
		return $this->initializeLazyObject()->zrevrange(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zrangebyscore($key, $start, $end, $options = null)
	{
		return $this->initializeLazyObject()->zrangebyscore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zrevrangebyscore($key, $start, $end, $options = null)
	{
		return $this->initializeLazyObject()->zrevrangebyscore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zrangestore($dst, $src, $start, $end, $options = null)
	{
		return $this->initializeLazyObject()->zrangestore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zrangebylex($key, $min, $max, $offset = -1, $count = -1)
	{
		return $this->initializeLazyObject()->zrangebylex(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zrevrangebylex($key, $max, $min, $offset = -1, $count = -1)
	{
		return $this->initializeLazyObject()->zrevrangebylex(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zrem($key, ...$args)
	{
		return $this->initializeLazyObject()->zrem(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zremrangebylex($key, $min, $max)
	{
		return $this->initializeLazyObject()->zremrangebylex(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zremrangebyrank($key, $start, $end)
	{
		return $this->initializeLazyObject()->zremrangebyrank(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zremrangebyscore($key, $min, $max)
	{
		return $this->initializeLazyObject()->zremrangebyscore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zcard($key)
	{
		return $this->initializeLazyObject()->zcard(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zcount($key, $min, $max)
	{
		return $this->initializeLazyObject()->zcount(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zdiff($keys, $options = null)
	{
		return $this->initializeLazyObject()->zdiff(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zdiffstore($dst, $keys)
	{
		return $this->initializeLazyObject()->zdiffstore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|float
	 */
	public function zincrby($key, $score, $mem)
	{
		return $this->initializeLazyObject()->zincrby(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zlexcount($key, $min, $max)
	{
		return $this->initializeLazyObject()->zlexcount(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zmscore($key, ...$mems)
	{
		return $this->initializeLazyObject()->zmscore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zinter($keys, $weights = null, $options = null)
	{
		return $this->initializeLazyObject()->zinter(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zintercard($keys, $limit = -1)
	{
		return $this->initializeLazyObject()->zintercard(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zinterstore($dst, $keys, $weights = null, $options = null)
	{
		return $this->initializeLazyObject()->zinterstore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zunion($keys, $weights = null, $options = null)
	{
		return $this->initializeLazyObject()->zunion(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|false|int
	 */
	public function zunionstore($dst, $keys, $weights = null, $options = null)
	{
		return $this->initializeLazyObject()->zunionstore(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zpopmin($key, $count = 1)
	{
		return $this->initializeLazyObject()->zpopmin(...\func_get_args());
	}

	/**
	 * @return \Relay\Relay|mixed[]|false
	 */
	public function zpopmax($key, $count = 1)
	{
		return $this->initializeLazyObject()->zpopmax(...\func_get_args());
	}

	public function _getKeys()
	{
		return $this->initializeLazyObject()->_getKeys(...\func_get_args());
	}
}
