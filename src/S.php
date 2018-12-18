<?hh // strict
namespace HHx;
use namespace HH\Lib\{C, Str, Vec, Dict};
use namespace HH\Asio;
class S {
	static ?Awaitable<mixed> $front;
	static ?\ConditionWaitHandle<mixed> $bell;
	static public function launch(Awaitable<mixed> $block): void {
		static::$front = Asio\v(vec[static::$front ?? Asio\later(), $block]);
		$stash_bell = static::$bell;
		static::$bell = ConditionWaitHandle::create(async { await static::$front; });
		$stash_bell?->succeed(null);
	}
	static public function run(): void {
		Asio\join(async {
			while(!Asio\has_finished(static::$front ?? async {})) {
				try {
					await static::$bell;
				}
				catch(\InvalidArgumentException $e) {
					if($e->getMessage() !== 'ConditionWaitHandle not notified by its child')
						throw $e;
				}
			}
		});
	}
}