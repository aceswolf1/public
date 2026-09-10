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

use TablePress\Symfony\Component\VarExporter\LazyObjectInterface;
use TablePress\Symfony\Contracts\Service\ResetInterface;

// Help opcache.preload discover always-needed symbols
class_exists(\TablePress\Symfony\Component\VarExporter\Internal\Hydrator::class);
class_exists(\TablePress\Symfony\Component\VarExporter\Internal\LazyObjectRegistry::class);
class_exists(\TablePress\Symfony\Component\VarExporter\Internal\LazyObjectState::class);

/**
 * @internal
 */
class RedisCluster6Proxy extends \RedisCluster implements ResetInterface, LazyObjectInterface
{
	use RedisCluster6ProxyTrait;
	use RedisProxyTrait {
		resetLazyObject as reset;
	}

	public function __construct($name, $seeds = null, $timeout = 0, $read_timeout = 0, $persistent = false, $auth = null, $context = null)
	{
		$this->initializeLazyObject()->__construct(...\func_get_args());
	}

	public function _compress($value): string
	{
		return $this->initializeLazyObject()->_compress(...\func_get_args());
	}

	public function _uncompress($value): string
	{
		return $this->initializeLazyObject()->_uncompress(...\func_get_args());
	}

	/**
	 * @return bool|string
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

	/**
	 * @return bool|string
	 */
	public function _prefix($key)
	{
		return $this->initializeLazyObject()->_prefix(...\func_get_args());
	}

	public function _masters(): array
	{
		return $this->initializeLazyObject()->_masters(...\func_get_args());
	}

	public function _redir(): ?string
	{
		return $this->initializeLazyObject()->_redir(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function acl($key_or_address, $subcmd, ...$args)
	{
		return $this->initializeLazyObject()->acl(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function append($key, $value)
	{
		return $this->initializeLazyObject()->append(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function bgrewriteaof($key_or_address)
	{
		return $this->initializeLazyObject()->bgrewriteaof(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function bgsave($key_or_address)
	{
		return $this->initializeLazyObject()->bgsave(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function bitcount($key, $start = 0, $end = -1, $bybit = false)
	{
		return $this->initializeLazyObject()->bitcount(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function bitop($operation, $deskey, $srckey, ...$otherkeys)
	{
		return $this->initializeLazyObject()->bitop(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function bitpos($key, $bit, $start = 0, $end = -1, $bybit = false)
	{
		return $this->initializeLazyObject()->bitpos(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|null
	 */
	public function blpop($key, $timeout_or_key, ...$extra_args)
	{
		return $this->initializeLazyObject()->blpop(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|null
	 */
	public function brpop($key, $timeout_or_key, ...$extra_args)
	{
		return $this->initializeLazyObject()->brpop(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function brpoplpush($srckey, $deskey, $timeout)
	{
		return $this->initializeLazyObject()->brpoplpush(...\func_get_args());
	}

	/**
	 * @return \Redis|false|string
	 */
	public function lmove($src, $dst, $wherefrom, $whereto)
	{
		return $this->initializeLazyObject()->lmove(...\func_get_args());
	}

	/**
	 * @return \Redis|false|string
	 */
	public function blmove($src, $dst, $wherefrom, $whereto, $timeout)
	{
		return $this->initializeLazyObject()->blmove(...\func_get_args());
	}

	public function bzpopmax($key, $timeout_or_key, ...$extra_args): array
	{
		return $this->initializeLazyObject()->bzpopmax(...\func_get_args());
	}

	public function bzpopmin($key, $timeout_or_key, ...$extra_args): array
	{
		return $this->initializeLazyObject()->bzpopmin(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|null
	 */
	public function bzmpop($timeout, $keys, $from, $count = 1)
	{
		return $this->initializeLazyObject()->bzmpop(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|null
	 */
	public function zmpop($keys, $from, $count = 1)
	{
		return $this->initializeLazyObject()->zmpop(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|null
	 */
	public function blmpop($timeout, $keys, $from, $count = 1)
	{
		return $this->initializeLazyObject()->blmpop(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|null
	 */
	public function lmpop($keys, $from, $count = 1)
	{
		return $this->initializeLazyObject()->lmpop(...\func_get_args());
	}

	public function clearlasterror(): bool
	{
		return $this->initializeLazyObject()->clearlasterror(...\func_get_args());
	}

	/**
	 * @return mixed[]|bool|string
	 */
	public function client($key_or_address, $subcommand, $arg = null)
	{
		return $this->initializeLazyObject()->client(...\func_get_args());
	}

	public function close(): bool
	{
		return $this->initializeLazyObject()->close(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function cluster($key_or_address, $command, ...$extra_args)
	{
		return $this->initializeLazyObject()->cluster(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function command(...$extra_args)
	{
		return $this->initializeLazyObject()->command(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function config($key_or_address, $subcommand, ...$extra_args)
	{
		return $this->initializeLazyObject()->config(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|int
	 */
	public function dbsize($key_or_address)
	{
		return $this->initializeLazyObject()->dbsize(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function copy($src, $dst, $options = null)
	{
		return $this->initializeLazyObject()->copy(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function decr($key, $by = 1)
	{
		return $this->initializeLazyObject()->decr(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function decrby($key, $value)
	{
		return $this->initializeLazyObject()->decrby(...\func_get_args());
	}

	public function decrbyfloat($key, $value): float
	{
		return $this->initializeLazyObject()->decrbyfloat(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function del($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->del(...\func_get_args());
	}

	public function discard(): bool
	{
		return $this->initializeLazyObject()->discard(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|string
	 */
	public function dump($key)
	{
		return $this->initializeLazyObject()->dump(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|string
	 */
	public function echo($key_or_address, $msg)
	{
		return $this->initializeLazyObject()->echo(...\func_get_args());
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
	public function evalsha($script_sha, $args = [], $num_keys = 0)
	{
		return $this->initializeLazyObject()->evalsha(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function evalsha_ro($script_sha, $args = [], $num_keys = 0)
	{
		return $this->initializeLazyObject()->evalsha_ro(...\func_get_args());
	}

	/**
	 * @return mixed[]|false
	 */
	public function exec()
	{
		return $this->initializeLazyObject()->exec(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function exists($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->exists(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function touch($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->touch(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function expire($key, $timeout, $mode = null)
	{
		return $this->initializeLazyObject()->expire(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function expireat($key, $timestamp, $mode = null)
	{
		return $this->initializeLazyObject()->expireat(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function expiretime($key)
	{
		return $this->initializeLazyObject()->expiretime(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function pexpiretime($key)
	{
		return $this->initializeLazyObject()->pexpiretime(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function flushall($key_or_address, $async = false)
	{
		return $this->initializeLazyObject()->flushall(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function flushdb($key_or_address, $async = false)
	{
		return $this->initializeLazyObject()->flushdb(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function geoadd($key, $lng, $lat, $member, ...$other_triples_and_options)
	{
		return $this->initializeLazyObject()->geoadd(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|float
	 */
	public function geodist($key, $src, $dest, $unit = null)
	{
		return $this->initializeLazyObject()->geodist(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function geohash($key, $member, ...$other_members)
	{
		return $this->initializeLazyObject()->geohash(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function geopos($key, $member, ...$other_members)
	{
		return $this->initializeLazyObject()->geopos(...\func_get_args());
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
	public function georadius_ro($key, $lng, $lat, $radius, $unit, $options = [])
	{
		return $this->initializeLazyObject()->georadius_ro(...\func_get_args());
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
	 * @return \RedisCluster|mixed[]
	 */
	public function geosearch($key, $position, $shape, $unit, $options = [])
	{
		return $this->initializeLazyObject()->geosearch(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|int
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
	 * @return \RedisCluster|false|int
	 */
	public function getbit($key, $value)
	{
		return $this->initializeLazyObject()->getbit(...\func_get_args());
	}

	public function getlasterror(): ?string
	{
		return $this->initializeLazyObject()->getlasterror(...\func_get_args());
	}

	public function getmode(): int
	{
		return $this->initializeLazyObject()->getmode(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function getoption($option)
	{
		return $this->initializeLazyObject()->getoption(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|string
	 */
	public function getrange($key, $start, $end)
	{
		return $this->initializeLazyObject()->getrange(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|int|string
	 */
	public function lcs($key1, $key2, $options = null)
	{
		return $this->initializeLazyObject()->lcs(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|string
	 */
	public function getset($key, $value)
	{
		return $this->initializeLazyObject()->getset(...\func_get_args());
	}

	/**
	 * @return mixed[]|false
	 */
	public function gettransferredbytes()
	{
		return $this->initializeLazyObject()->gettransferredbytes(...\func_get_args());
	}

	public function cleartransferredbytes(): void
	{
		$this->initializeLazyObject()->cleartransferredbytes(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function hdel($key, $member, ...$other_members)
	{
		return $this->initializeLazyObject()->hdel(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function hexists($key, $member)
	{
		return $this->initializeLazyObject()->hexists(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function hget($key, $member)
	{
		return $this->initializeLazyObject()->hget(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function hgetall($key)
	{
		return $this->initializeLazyObject()->hgetall(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function hincrby($key, $member, $value)
	{
		return $this->initializeLazyObject()->hincrby(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|float
	 */
	public function hincrbyfloat($key, $member, $value)
	{
		return $this->initializeLazyObject()->hincrbyfloat(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function hkeys($key)
	{
		return $this->initializeLazyObject()->hkeys(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function hlen($key)
	{
		return $this->initializeLazyObject()->hlen(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function hmget($key, $keys)
	{
		return $this->initializeLazyObject()->hmget(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function hmset($key, $key_values)
	{
		return $this->initializeLazyObject()->hmset(...\func_get_args());
	}

	/**
	 * @return mixed[]|bool
	 */
	public function hscan($key, &$iterator, $pattern = null, $count = 0)
	{
		return $this->initializeLazyObject()->hscan($key, $iterator, ...\array_slice(\func_get_args(), 2));
	}

	/**
	 * @return \RedisCluster|mixed[]|string
	 */
	public function hrandfield($key, $options = null)
	{
		return $this->initializeLazyObject()->hrandfield(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function hset($key, $member, $value)
	{
		return $this->initializeLazyObject()->hset(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function hsetnx($key, $member, $value)
	{
		return $this->initializeLazyObject()->hsetnx(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function hstrlen($key, $field)
	{
		return $this->initializeLazyObject()->hstrlen(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function hvals($key)
	{
		return $this->initializeLazyObject()->hvals(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function incr($key, $by = 1)
	{
		return $this->initializeLazyObject()->incr(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function incrby($key, $value)
	{
		return $this->initializeLazyObject()->incrby(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|float
	 */
	public function incrbyfloat($key, $value)
	{
		return $this->initializeLazyObject()->incrbyfloat(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function info($key_or_address, ...$sections)
	{
		return $this->initializeLazyObject()->info(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function keys($pattern)
	{
		return $this->initializeLazyObject()->keys(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function lastsave($key_or_address)
	{
		return $this->initializeLazyObject()->lastsave(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|string
	 */
	public function lget($key, $index)
	{
		return $this->initializeLazyObject()->lget(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function lindex($key, $index)
	{
		return $this->initializeLazyObject()->lindex(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function linsert($key, $pos, $pivot, $value)
	{
		return $this->initializeLazyObject()->linsert(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function llen($key)
	{
		return $this->initializeLazyObject()->llen(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool|string
	 */
	public function lpop($key, $count = 0)
	{
		return $this->initializeLazyObject()->lpop(...\func_get_args());
	}

	/**
	 * @return \Redis|mixed[]|bool|int|null
	 */
	public function lpos($key, $value, $options = null)
	{
		return $this->initializeLazyObject()->lpos(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function lpush($key, $value, ...$other_values)
	{
		return $this->initializeLazyObject()->lpush(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function lpushx($key, $value)
	{
		return $this->initializeLazyObject()->lpushx(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function lrange($key, $start, $end)
	{
		return $this->initializeLazyObject()->lrange(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function lrem($key, $value, $count = 0)
	{
		return $this->initializeLazyObject()->lrem(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function lset($key, $index, $value)
	{
		return $this->initializeLazyObject()->lset(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function ltrim($key, $start, $end)
	{
		return $this->initializeLazyObject()->ltrim(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function mget($keys)
	{
		return $this->initializeLazyObject()->mget(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function mset($key_values)
	{
		return $this->initializeLazyObject()->mset(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function msetnx($key_values)
	{
		return $this->initializeLazyObject()->msetnx(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function multi($value = \Redis::MULTI)
	{
		return $this->initializeLazyObject()->multi(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int|string
	 */
	public function object($subcommand, $key)
	{
		return $this->initializeLazyObject()->object(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function persist($key)
	{
		return $this->initializeLazyObject()->persist(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function pexpire($key, $timeout, $mode = null)
	{
		return $this->initializeLazyObject()->pexpire(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function pexpireat($key, $timestamp, $mode = null)
	{
		return $this->initializeLazyObject()->pexpireat(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function pfadd($key, $elements)
	{
		return $this->initializeLazyObject()->pfadd(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function pfcount($key)
	{
		return $this->initializeLazyObject()->pfcount(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function pfmerge($key, $keys)
	{
		return $this->initializeLazyObject()->pfmerge(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function ping($key_or_address, $message = null)
	{
		return $this->initializeLazyObject()->ping(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function psetex($key, $timeout, $value)
	{
		return $this->initializeLazyObject()->psetex(...\func_get_args());
	}

	public function psubscribe($patterns, $callback): void
	{
		$this->initializeLazyObject()->psubscribe(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function pttl($key)
	{
		return $this->initializeLazyObject()->pttl(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function pubsub($key_or_address, ...$values)
	{
		return $this->initializeLazyObject()->pubsub(...\func_get_args());
	}

	/**
	 * @return mixed[]|bool
	 */
	public function punsubscribe($pattern, ...$other_patterns)
	{
		return $this->initializeLazyObject()->punsubscribe(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|string
	 */
	public function randomkey($key_or_address)
	{
		return $this->initializeLazyObject()->randomkey(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function rawcommand($key_or_address, $command, ...$args)
	{
		return $this->initializeLazyObject()->rawcommand(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function rename($key_src, $key_dst)
	{
		return $this->initializeLazyObject()->rename(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function renamenx($key, $newkey)
	{
		return $this->initializeLazyObject()->renamenx(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function restore($key, $timeout, $value, $options = null)
	{
		return $this->initializeLazyObject()->restore(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function role($key_or_address)
	{
		return $this->initializeLazyObject()->role(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool|string
	 */
	public function rpop($key, $count = 0)
	{
		return $this->initializeLazyObject()->rpop(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|string
	 */
	public function rpoplpush($src, $dst)
	{
		return $this->initializeLazyObject()->rpoplpush(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function rpush($key, ...$elements)
	{
		return $this->initializeLazyObject()->rpush(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function rpushx($key, $value)
	{
		return $this->initializeLazyObject()->rpushx(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function sadd($key, $value, ...$other_values)
	{
		return $this->initializeLazyObject()->sadd(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|int
	 */
	public function saddarray($key, $values)
	{
		return $this->initializeLazyObject()->saddarray(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function save($key_or_address)
	{
		return $this->initializeLazyObject()->save(...\func_get_args());
	}

	/**
	 * @return mixed[]|bool
	 */
	public function scan(&$iterator, $key_or_address, $pattern = null, $count = 0)
	{
		return $this->initializeLazyObject()->scan($iterator, ...\array_slice(\func_get_args(), 1));
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function scard($key)
	{
		return $this->initializeLazyObject()->scard(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function script($key_or_address, ...$args)
	{
		return $this->initializeLazyObject()->script(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function sdiff($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sdiff(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function sdiffstore($dst, $key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sdiffstore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool|string
	 */
	public function set($key, $value, $options = null)
	{
		return $this->initializeLazyObject()->set(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function setbit($key, $offset, $onoff)
	{
		return $this->initializeLazyObject()->setbit(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function setex($key, $expire, $value)
	{
		return $this->initializeLazyObject()->setex(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function setnx($key, $value)
	{
		return $this->initializeLazyObject()->setnx(...\func_get_args());
	}

	public function setoption($option, $value): bool
	{
		return $this->initializeLazyObject()->setoption(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function setrange($key, $offset, $value)
	{
		return $this->initializeLazyObject()->setrange(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function sinter($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sinter(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function sintercard($keys, $limit = -1)
	{
		return $this->initializeLazyObject()->sintercard(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function sinterstore($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sinterstore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function sismember($key, $value)
	{
		return $this->initializeLazyObject()->sismember(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function smismember($key, $member, ...$other_members)
	{
		return $this->initializeLazyObject()->smismember(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function slowlog($key_or_address, ...$args)
	{
		return $this->initializeLazyObject()->slowlog(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function smembers($key)
	{
		return $this->initializeLazyObject()->smembers(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function smove($src, $dst, $member)
	{
		return $this->initializeLazyObject()->smove(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool|int|string
	 */
	public function sort($key, $options = null)
	{
		return $this->initializeLazyObject()->sort(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool|int|string
	 */
	public function sort_ro($key, $options = null)
	{
		return $this->initializeLazyObject()->sort_ro(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|string
	 */
	public function spop($key, $count = 0)
	{
		return $this->initializeLazyObject()->spop(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|string
	 */
	public function srandmember($key, $count = 0)
	{
		return $this->initializeLazyObject()->srandmember(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function srem($key, $value, ...$other_values)
	{
		return $this->initializeLazyObject()->srem(...\func_get_args());
	}

	/**
	 * @return mixed[]|false
	 */
	public function sscan($key, &$iterator, $pattern = null, $count = 0)
	{
		return $this->initializeLazyObject()->sscan($key, $iterator, ...\array_slice(\func_get_args(), 2));
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function strlen($key)
	{
		return $this->initializeLazyObject()->strlen(...\func_get_args());
	}

	public function subscribe($channels, $cb): void
	{
		$this->initializeLazyObject()->subscribe(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function sunion($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sunion(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function sunionstore($dst, $key, ...$other_keys)
	{
		return $this->initializeLazyObject()->sunionstore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function time($key_or_address)
	{
		return $this->initializeLazyObject()->time(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function ttl($key)
	{
		return $this->initializeLazyObject()->ttl(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function type($key)
	{
		return $this->initializeLazyObject()->type(...\func_get_args());
	}

	/**
	 * @return mixed[]|bool
	 */
	public function unsubscribe($channels)
	{
		return $this->initializeLazyObject()->unsubscribe(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function unlink($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->unlink(...\func_get_args());
	}

	public function unwatch(): bool
	{
		return $this->initializeLazyObject()->unwatch(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|bool
	 */
	public function watch($key, ...$other_keys)
	{
		return $this->initializeLazyObject()->watch(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function xack($key, $group, $ids)
	{
		return $this->initializeLazyObject()->xack(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|string
	 */
	public function xadd($key, $id, $values, $maxlen = 0, $approx = false)
	{
		return $this->initializeLazyObject()->xadd(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false|string
	 */
	public function xclaim($key, $group, $consumer, $min_iddle, $ids, $options)
	{
		return $this->initializeLazyObject()->xclaim(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function xdel($key, $ids)
	{
		return $this->initializeLazyObject()->xdel(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function xgroup($operation, $key = null, $group = null, $id_or_consumer = null, $mkstream = false, $entries_read = -2)
	{
		return $this->initializeLazyObject()->xgroup(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function xautoclaim($key, $group, $consumer, $min_idle, $start, $count = -1, $justid = false)
	{
		return $this->initializeLazyObject()->xautoclaim(...\func_get_args());
	}

	/**
	 * @return mixed
	 */
	public function xinfo($operation, $arg1 = null, $arg2 = null, $count = -1)
	{
		return $this->initializeLazyObject()->xinfo(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function xlen($key)
	{
		return $this->initializeLazyObject()->xlen(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function xpending($key, $group, $start = null, $end = null, $count = -1, $consumer = null)
	{
		return $this->initializeLazyObject()->xpending(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function xrange($key, $start, $end, $count = -1)
	{
		return $this->initializeLazyObject()->xrange(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function xread($streams, $count = -1, $block = -1)
	{
		return $this->initializeLazyObject()->xread(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function xreadgroup($group, $consumer, $streams, $count = 1, $block = 1)
	{
		return $this->initializeLazyObject()->xreadgroup(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function xrevrange($key, $start, $end, $count = -1)
	{
		return $this->initializeLazyObject()->xrevrange(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function xtrim($key, $maxlen, $approx = false, $minid = false, $limit = -1)
	{
		return $this->initializeLazyObject()->xtrim(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|float|int
	 */
	public function zadd($key, $score_or_options, ...$more_scores_and_mems)
	{
		return $this->initializeLazyObject()->zadd(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zcard($key)
	{
		return $this->initializeLazyObject()->zcard(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zcount($key, $start, $end)
	{
		return $this->initializeLazyObject()->zcount(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|float
	 */
	public function zincrby($key, $value, $member)
	{
		return $this->initializeLazyObject()->zincrby(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zinterstore($dst, $keys, $weights = null, $aggregate = null)
	{
		return $this->initializeLazyObject()->zinterstore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zintercard($keys, $limit = -1)
	{
		return $this->initializeLazyObject()->zintercard(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zlexcount($key, $min, $max)
	{
		return $this->initializeLazyObject()->zlexcount(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function zpopmax($key, $value = null)
	{
		return $this->initializeLazyObject()->zpopmax(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function zpopmin($key, $value = null)
	{
		return $this->initializeLazyObject()->zpopmin(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function zrange($key, $start, $end, $options = null)
	{
		return $this->initializeLazyObject()->zrange(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zrangestore($dstkey, $srckey, $start, $end, $options = null)
	{
		return $this->initializeLazyObject()->zrangestore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|string
	 */
	public function zrandmember($key, $options = null)
	{
		return $this->initializeLazyObject()->zrandmember(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function zrangebylex($key, $min, $max, $offset = -1, $count = -1)
	{
		return $this->initializeLazyObject()->zrangebylex(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function zrangebyscore($key, $start, $end, $options = [])
	{
		return $this->initializeLazyObject()->zrangebyscore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zrank($key, $member)
	{
		return $this->initializeLazyObject()->zrank(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zrem($key, $value, ...$other_values)
	{
		return $this->initializeLazyObject()->zrem(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zremrangebylex($key, $min, $max)
	{
		return $this->initializeLazyObject()->zremrangebylex(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zremrangebyrank($key, $min, $max)
	{
		return $this->initializeLazyObject()->zremrangebyrank(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zremrangebyscore($key, $min, $max)
	{
		return $this->initializeLazyObject()->zremrangebyscore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function zrevrange($key, $min, $max, $options = null)
	{
		return $this->initializeLazyObject()->zrevrange(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function zrevrangebylex($key, $min, $max, $options = null)
	{
		return $this->initializeLazyObject()->zrevrangebylex(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function zrevrangebyscore($key, $min, $max, $options = null)
	{
		return $this->initializeLazyObject()->zrevrangebyscore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zrevrank($key, $member)
	{
		return $this->initializeLazyObject()->zrevrank(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|bool
	 */
	public function zscan($key, &$iterator, $pattern = null, $count = 0)
	{
		return $this->initializeLazyObject()->zscan($key, $iterator, ...\array_slice(\func_get_args(), 2));
	}

	/**
	 * @return \RedisCluster|false|float
	 */
	public function zscore($key, $member)
	{
		return $this->initializeLazyObject()->zscore(...\func_get_args());
	}

	/**
	 * @return \Redis|mixed[]|false
	 */
	public function zmscore($key, $member, ...$other_members)
	{
		return $this->initializeLazyObject()->zmscore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zunionstore($dst, $keys, $weights = null, $aggregate = null)
	{
		return $this->initializeLazyObject()->zunionstore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function zinter($keys, $weights = null, $options = null)
	{
		return $this->initializeLazyObject()->zinter(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|false|int
	 */
	public function zdiffstore($dst, $keys)
	{
		return $this->initializeLazyObject()->zdiffstore(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function zunion($keys, $weights = null, $options = null)
	{
		return $this->initializeLazyObject()->zunion(...\func_get_args());
	}

	/**
	 * @return \RedisCluster|mixed[]|false
	 */
	public function zdiff($keys, $options = null)
	{
		return $this->initializeLazyObject()->zdiff(...\func_get_args());
	}
}
