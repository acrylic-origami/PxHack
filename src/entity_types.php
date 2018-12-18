<?hh // strict
namespace HHx;

type NextSignal = mixed; // void | true
type Consumer<-T> = (function(T): Awaitable<NextSignal>); // async might be work, mixed might be cancellation
type Supplier<+T> = (function(Consumer<T>): Awaitable<mixed>); // async is just the lifetime of the chain up to this point
type Operator<-Tu, +Tv> = (function(Supplier<Tu>): Supplier<Tv>);

const bool CANCEL = true; // return this (really anything non-null) from any consumer to stop the flow