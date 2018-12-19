<?hh // strict
namespace HPx;
use namespace HH\Asio;
class ShareIterator<+T> implements SharableIterator<T> {
	private Pointer<int> $count;
	private Awaitable<?(mixed, T)> $next;
	public function __construct(private AsyncIterator<T> $source) {
		$this->count = new Pointer(0);
		$this->next = $source->next();
	}
	public async function next(): Awaitable<?(mixed, T)> {
		$stash_count = $this->count->get();
		if(Asio\has_finished($this->next))
			$this->next = $this->source->next();
		
		$next = await $this->next;
		if($this->count->get() === $stash_count) {
			$this->count->set($stash_count + 1);
			return $next;
		}
	}
}