<?hh // strict
/* Copyright (c) 2015, Facebook, Inc.
 * All rights reserved.
 *
 *  This source code is licensed under the BSD-style license found in the
 *  LICENSE file in the root directory of this source tree. An additional grant
 *  of patent rights can be found in the PATENTS file in the same directory.
 */
namespace HPx;
use namespace HH\Asio;
<<__ConsistentConstruct>>
class AsyncCondition<T> {
  private ?Awaitable<T> $condition = null;
  
  protected function __construct() {}

  /**
   * Notify the condition variable of success and set the result.
   */
  final public function succeed(T $result): void {
    if ($this->condition === null) {
      $this->condition = async { return $result; };
    } else {
      invariant(
        $this->condition instanceof ConditionWaitHandle,
        'Unable to notify AsyncCondition twice',
      );
      $this->condition->succeed($result);
    }
  }

  /**
   * Notify the condition variable of failure and set the exception.
   */
  final public function fail(\Exception $exception): void {
    if ($this->condition === null) {
      $this->condition = async { throw $exception; };
    } else {
      invariant(
        $this->condition instanceof ConditionWaitHandle,
        'Unable to notify AsyncCondition twice',
      );
      $this->condition->fail($exception);
    }
  }
  
  final public function isNotified(): bool {
    return !\is_null($this->condition) && (!($this->condition instanceof ConditionWaitHandle) || Asio\has_finished($this->condition));
  }
  
  final public function set(Awaitable<mixed> $lifetime): void {
    $this->condition = $this->condition ?? ConditionWaitHandle::create(async { await $lifetime; });
  }
  
  final public function get(): Awaitable<T> {
    $condition = $this->condition;
    invariant(!\is_null($condition), 'Set before getting.');
    return $condition;
  }
  
  final public static async function create((function(this): Awaitable<mixed>) $f): Awaitable<mixed> {
    $target = new static();
    $target->set($f($target));
    return $target->get();
  }
  
  final public static async function create_many((function((function(): this)): Awaitable<mixed>) $f): AsyncIterator<mixed> {
    // $running = new Pointer(false);
    $core = new NullablePointer();
    $bells = vec[new static()];
    $factory = () ==> {
      $target = new static();
      $_core = $core->get();
      if($_core !== null) {
        $target->set($_core);
      }
      $bells[] = $target;
      return $target;
    };
    
    $user = $f($factory);
    $core->set($user);
    if($bells[0]->isNotified())
      $bells[] = new static();
    $bells[\count($bells) - 1]->set($user);
    for($i = 0; $i < \count($bells); $i++) {
      $v = await $bells[$i]->get();
      yield $v;
    }
  }
}