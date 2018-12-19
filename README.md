# Hack ProactiveX

Streaming framework for async-await languages, sitting in the middle of ReactiveX and InteractiveX. This is the Hack implementation. See `test/` for examples. Namespace is `Px`.

## The maximum is in the middle

In general, ProactiveX (working name) is an experiment to show that ReactiveX and InteractiveX is a spectrum rather than a dichotomy, and to implement operators as encapsulations rather than replacements of user-written code. In particular, this project explores Operators implemented as functions of this type:

```
Operator<in Tu, out Tv, T1, ..., Tn> := (T1 x1, ..., Tn xn) -> Supplier<Tu> -> Supplier<Tv>
Supplier<out T> := Consumer<T> -> Awaitable<void>
Consumer<in T> := T -> Awaitable<void|CANCEL>
	for any CANCEL != void
```

Which is the solution to the following specification of operators:

1. Take a stream from another operator
2. Be parameterized by values from the ambient scope
3. Take a consumer that consumes values one at a time (and might define its state outside of the operator to make a custom state machine)
4. Let the consumer decide at every element (i.e. the only instances it has control) whether it wants to continue consuming
5. Be transparent to exceptions which pass from the consumer to the outer scope
6. Have an `Awaitable` that represents the end of consumption so we can do things upon completion.

This definition has some very nice properties, including naturally-propagating cancellation, fine-grained control over the extent and behavior of custom scheduling, an implementation free of ConditionWaitHandle (outside the scheduler) and an intrinsic simplicity of implementing custom operators.

For example, this implementation might not ever include `merge` because it's so straightforward &mdash; here's a verbose version:

```hack
// given `vec<Supplier<T>> $suppliers`;
$merged = <T>(Consumer<T> $downstream) ==>
	HH\Asio\v(HH\Lib\Vec\map($suppliers, (Supplier<T> $supplier) ==> $supplier($downstream)));
// e.g. await $merged(fun('print'));
```

See the [full article describing HHx](//lam.io/blog/HHx).