/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/async-sema/lib/index.js"
/*!**********************************************!*\
  !*** ./node_modules/async-sema/lib/index.js ***!
  \**********************************************/
(__unused_webpack_module, exports, __webpack_require__) {


var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.RateLimit = exports.Sema = void 0;
const events_1 = __importDefault(__webpack_require__(/*! events */ "./node_modules/events/events.js"));
function arrayMove(src, srcIndex, dst, dstIndex, len) {
    for (let j = 0; j < len; ++j) {
        dst[j + dstIndex] = src[j + srcIndex];
        src[j + srcIndex] = void 0;
    }
}
function pow2AtLeast(n) {
    n = n >>> 0;
    n = n - 1;
    n = n | (n >> 1);
    n = n | (n >> 2);
    n = n | (n >> 4);
    n = n | (n >> 8);
    n = n | (n >> 16);
    return n + 1;
}
function getCapacity(capacity) {
    return pow2AtLeast(Math.min(Math.max(16, capacity), 1073741824));
}
// Deque is based on https://github.com/petkaantonov/deque/blob/master/js/deque.js
// Released under the MIT License: https://github.com/petkaantonov/deque/blob/6ef4b6400ad3ba82853fdcc6531a38eb4f78c18c/LICENSE
class Deque {
    constructor(capacity) {
        this._capacity = getCapacity(capacity);
        this._length = 0;
        this._front = 0;
        this.arr = [];
    }
    push(item) {
        const length = this._length;
        this.checkCapacity(length + 1);
        const i = (this._front + length) & (this._capacity - 1);
        this.arr[i] = item;
        this._length = length + 1;
        return length + 1;
    }
    pop() {
        const length = this._length;
        if (length === 0) {
            return void 0;
        }
        const i = (this._front + length - 1) & (this._capacity - 1);
        const ret = this.arr[i];
        this.arr[i] = void 0;
        this._length = length - 1;
        return ret;
    }
    shift() {
        const length = this._length;
        if (length === 0) {
            return void 0;
        }
        const front = this._front;
        const ret = this.arr[front];
        this.arr[front] = void 0;
        this._front = (front + 1) & (this._capacity - 1);
        this._length = length - 1;
        return ret;
    }
    get length() {
        return this._length;
    }
    checkCapacity(size) {
        if (this._capacity < size) {
            this.resizeTo(getCapacity(this._capacity * 1.5 + 16));
        }
    }
    resizeTo(capacity) {
        const oldCapacity = this._capacity;
        this._capacity = capacity;
        const front = this._front;
        const length = this._length;
        if (front + length > oldCapacity) {
            const moveItemsCount = (front + length) & (oldCapacity - 1);
            arrayMove(this.arr, 0, this.arr, oldCapacity, moveItemsCount);
        }
    }
}
class ReleaseEmitter extends events_1.default {
}
function isFn(x) {
    return typeof x === 'function';
}
function defaultInit() {
    return '1';
}
class Sema {
    constructor(nr, { initFn = defaultInit, pauseFn, resumeFn, capacity = 10, } = {}) {
        if (isFn(pauseFn) !== isFn(resumeFn)) {
            throw new Error('pauseFn and resumeFn must be both set for pausing');
        }
        this.nrTokens = nr;
        this.free = new Deque(nr);
        this.waiting = new Deque(capacity);
        this.releaseEmitter = new ReleaseEmitter();
        this.noTokens = initFn === defaultInit;
        this.pauseFn = pauseFn;
        this.resumeFn = resumeFn;
        this.paused = false;
        this.releaseEmitter.on('release', (token) => {
            const p = this.waiting.shift();
            if (p) {
                p.resolve(token);
            }
            else {
                if (this.resumeFn && this.paused) {
                    this.paused = false;
                    this.resumeFn();
                }
                this.free.push(token);
            }
        });
        for (let i = 0; i < nr; i++) {
            this.free.push(initFn());
        }
    }
    tryAcquire() {
        return this.free.pop();
    }
    async acquire() {
        let token = this.tryAcquire();
        if (token !== void 0) {
            return token;
        }
        return new Promise((resolve, reject) => {
            if (this.pauseFn && !this.paused) {
                this.paused = true;
                this.pauseFn();
            }
            this.waiting.push({ resolve, reject });
        });
    }
    release(token) {
        this.releaseEmitter.emit('release', this.noTokens ? '1' : token);
    }
    drain() {
        const a = new Array(this.nrTokens);
        for (let i = 0; i < this.nrTokens; i++) {
            a[i] = this.acquire();
        }
        return Promise.all(a);
    }
    nrWaiting() {
        return this.waiting.length;
    }
}
exports.Sema = Sema;
function RateLimit(rps, { timeUnit = 1000, uniformDistribution = false, } = {}) {
    const sema = new Sema(uniformDistribution ? 1 : rps);
    const delay = uniformDistribution ? timeUnit / rps : timeUnit;
    return async function rl() {
        await sema.acquire();
        setTimeout(() => sema.release(), delay);
    };
}
exports.RateLimit = RateLimit;


/***/ },

/***/ "./node_modules/events/events.js"
/*!***************************************!*\
  !*** ./node_modules/events/events.js ***!
  \***************************************/
(module) {

// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.



var R = typeof Reflect === 'object' ? Reflect : null
var ReflectApply = R && typeof R.apply === 'function'
  ? R.apply
  : function ReflectApply(target, receiver, args) {
    return Function.prototype.apply.call(target, receiver, args);
  }

var ReflectOwnKeys
if (R && typeof R.ownKeys === 'function') {
  ReflectOwnKeys = R.ownKeys
} else if (Object.getOwnPropertySymbols) {
  ReflectOwnKeys = function ReflectOwnKeys(target) {
    return Object.getOwnPropertyNames(target)
      .concat(Object.getOwnPropertySymbols(target));
  };
} else {
  ReflectOwnKeys = function ReflectOwnKeys(target) {
    return Object.getOwnPropertyNames(target);
  };
}

function ProcessEmitWarning(warning) {
  if (console && console.warn) console.warn(warning);
}

var NumberIsNaN = Number.isNaN || function NumberIsNaN(value) {
  return value !== value;
}

function EventEmitter() {
  EventEmitter.init.call(this);
}
module.exports = EventEmitter;
module.exports.once = once;

// Backwards-compat with node 0.10.x
EventEmitter.EventEmitter = EventEmitter;

EventEmitter.prototype._events = undefined;
EventEmitter.prototype._eventsCount = 0;
EventEmitter.prototype._maxListeners = undefined;

// By default EventEmitters will print a warning if more than 10 listeners are
// added to it. This is a useful default which helps finding memory leaks.
var defaultMaxListeners = 10;

function checkListener(listener) {
  if (typeof listener !== 'function') {
    throw new TypeError('The "listener" argument must be of type Function. Received type ' + typeof listener);
  }
}

Object.defineProperty(EventEmitter, 'defaultMaxListeners', {
  enumerable: true,
  get: function() {
    return defaultMaxListeners;
  },
  set: function(arg) {
    if (typeof arg !== 'number' || arg < 0 || NumberIsNaN(arg)) {
      throw new RangeError('The value of "defaultMaxListeners" is out of range. It must be a non-negative number. Received ' + arg + '.');
    }
    defaultMaxListeners = arg;
  }
});

EventEmitter.init = function() {

  if (this._events === undefined ||
      this._events === Object.getPrototypeOf(this)._events) {
    this._events = Object.create(null);
    this._eventsCount = 0;
  }

  this._maxListeners = this._maxListeners || undefined;
};

// Obviously not all Emitters should be limited to 10. This function allows
// that to be increased. Set to zero for unlimited.
EventEmitter.prototype.setMaxListeners = function setMaxListeners(n) {
  if (typeof n !== 'number' || n < 0 || NumberIsNaN(n)) {
    throw new RangeError('The value of "n" is out of range. It must be a non-negative number. Received ' + n + '.');
  }
  this._maxListeners = n;
  return this;
};

function _getMaxListeners(that) {
  if (that._maxListeners === undefined)
    return EventEmitter.defaultMaxListeners;
  return that._maxListeners;
}

EventEmitter.prototype.getMaxListeners = function getMaxListeners() {
  return _getMaxListeners(this);
};

EventEmitter.prototype.emit = function emit(type) {
  var args = [];
  for (var i = 1; i < arguments.length; i++) args.push(arguments[i]);
  var doError = (type === 'error');

  var events = this._events;
  if (events !== undefined)
    doError = (doError && events.error === undefined);
  else if (!doError)
    return false;

  // If there is no 'error' event listener then throw.
  if (doError) {
    var er;
    if (args.length > 0)
      er = args[0];
    if (er instanceof Error) {
      // Note: The comments on the `throw` lines are intentional, they show
      // up in Node's output if this results in an unhandled exception.
      throw er; // Unhandled 'error' event
    }
    // At least give some kind of context to the user
    var err = new Error('Unhandled error.' + (er ? ' (' + er.message + ')' : ''));
    err.context = er;
    throw err; // Unhandled 'error' event
  }

  var handler = events[type];

  if (handler === undefined)
    return false;

  if (typeof handler === 'function') {
    ReflectApply(handler, this, args);
  } else {
    var len = handler.length;
    var listeners = arrayClone(handler, len);
    for (var i = 0; i < len; ++i)
      ReflectApply(listeners[i], this, args);
  }

  return true;
};

function _addListener(target, type, listener, prepend) {
  var m;
  var events;
  var existing;

  checkListener(listener);

  events = target._events;
  if (events === undefined) {
    events = target._events = Object.create(null);
    target._eventsCount = 0;
  } else {
    // To avoid recursion in the case that type === "newListener"! Before
    // adding it to the listeners, first emit "newListener".
    if (events.newListener !== undefined) {
      target.emit('newListener', type,
                  listener.listener ? listener.listener : listener);

      // Re-assign `events` because a newListener handler could have caused the
      // this._events to be assigned to a new object
      events = target._events;
    }
    existing = events[type];
  }

  if (existing === undefined) {
    // Optimize the case of one listener. Don't need the extra array object.
    existing = events[type] = listener;
    ++target._eventsCount;
  } else {
    if (typeof existing === 'function') {
      // Adding the second element, need to change to array.
      existing = events[type] =
        prepend ? [listener, existing] : [existing, listener];
      // If we've already got an array, just append.
    } else if (prepend) {
      existing.unshift(listener);
    } else {
      existing.push(listener);
    }

    // Check for listener leak
    m = _getMaxListeners(target);
    if (m > 0 && existing.length > m && !existing.warned) {
      existing.warned = true;
      // No error code for this since it is a Warning
      // eslint-disable-next-line no-restricted-syntax
      var w = new Error('Possible EventEmitter memory leak detected. ' +
                          existing.length + ' ' + String(type) + ' listeners ' +
                          'added. Use emitter.setMaxListeners() to ' +
                          'increase limit');
      w.name = 'MaxListenersExceededWarning';
      w.emitter = target;
      w.type = type;
      w.count = existing.length;
      ProcessEmitWarning(w);
    }
  }

  return target;
}

EventEmitter.prototype.addListener = function addListener(type, listener) {
  return _addListener(this, type, listener, false);
};

EventEmitter.prototype.on = EventEmitter.prototype.addListener;

EventEmitter.prototype.prependListener =
    function prependListener(type, listener) {
      return _addListener(this, type, listener, true);
    };

function onceWrapper() {
  if (!this.fired) {
    this.target.removeListener(this.type, this.wrapFn);
    this.fired = true;
    if (arguments.length === 0)
      return this.listener.call(this.target);
    return this.listener.apply(this.target, arguments);
  }
}

function _onceWrap(target, type, listener) {
  var state = { fired: false, wrapFn: undefined, target: target, type: type, listener: listener };
  var wrapped = onceWrapper.bind(state);
  wrapped.listener = listener;
  state.wrapFn = wrapped;
  return wrapped;
}

EventEmitter.prototype.once = function once(type, listener) {
  checkListener(listener);
  this.on(type, _onceWrap(this, type, listener));
  return this;
};

EventEmitter.prototype.prependOnceListener =
    function prependOnceListener(type, listener) {
      checkListener(listener);
      this.prependListener(type, _onceWrap(this, type, listener));
      return this;
    };

// Emits a 'removeListener' event if and only if the listener was removed.
EventEmitter.prototype.removeListener =
    function removeListener(type, listener) {
      var list, events, position, i, originalListener;

      checkListener(listener);

      events = this._events;
      if (events === undefined)
        return this;

      list = events[type];
      if (list === undefined)
        return this;

      if (list === listener || list.listener === listener) {
        if (--this._eventsCount === 0)
          this._events = Object.create(null);
        else {
          delete events[type];
          if (events.removeListener)
            this.emit('removeListener', type, list.listener || listener);
        }
      } else if (typeof list !== 'function') {
        position = -1;

        for (i = list.length - 1; i >= 0; i--) {
          if (list[i] === listener || list[i].listener === listener) {
            originalListener = list[i].listener;
            position = i;
            break;
          }
        }

        if (position < 0)
          return this;

        if (position === 0)
          list.shift();
        else {
          spliceOne(list, position);
        }

        if (list.length === 1)
          events[type] = list[0];

        if (events.removeListener !== undefined)
          this.emit('removeListener', type, originalListener || listener);
      }

      return this;
    };

EventEmitter.prototype.off = EventEmitter.prototype.removeListener;

EventEmitter.prototype.removeAllListeners =
    function removeAllListeners(type) {
      var listeners, events, i;

      events = this._events;
      if (events === undefined)
        return this;

      // not listening for removeListener, no need to emit
      if (events.removeListener === undefined) {
        if (arguments.length === 0) {
          this._events = Object.create(null);
          this._eventsCount = 0;
        } else if (events[type] !== undefined) {
          if (--this._eventsCount === 0)
            this._events = Object.create(null);
          else
            delete events[type];
        }
        return this;
      }

      // emit removeListener for all listeners on all events
      if (arguments.length === 0) {
        var keys = Object.keys(events);
        var key;
        for (i = 0; i < keys.length; ++i) {
          key = keys[i];
          if (key === 'removeListener') continue;
          this.removeAllListeners(key);
        }
        this.removeAllListeners('removeListener');
        this._events = Object.create(null);
        this._eventsCount = 0;
        return this;
      }

      listeners = events[type];

      if (typeof listeners === 'function') {
        this.removeListener(type, listeners);
      } else if (listeners !== undefined) {
        // LIFO order
        for (i = listeners.length - 1; i >= 0; i--) {
          this.removeListener(type, listeners[i]);
        }
      }

      return this;
    };

function _listeners(target, type, unwrap) {
  var events = target._events;

  if (events === undefined)
    return [];

  var evlistener = events[type];
  if (evlistener === undefined)
    return [];

  if (typeof evlistener === 'function')
    return unwrap ? [evlistener.listener || evlistener] : [evlistener];

  return unwrap ?
    unwrapListeners(evlistener) : arrayClone(evlistener, evlistener.length);
}

EventEmitter.prototype.listeners = function listeners(type) {
  return _listeners(this, type, true);
};

EventEmitter.prototype.rawListeners = function rawListeners(type) {
  return _listeners(this, type, false);
};

EventEmitter.listenerCount = function(emitter, type) {
  if (typeof emitter.listenerCount === 'function') {
    return emitter.listenerCount(type);
  } else {
    return listenerCount.call(emitter, type);
  }
};

EventEmitter.prototype.listenerCount = listenerCount;
function listenerCount(type) {
  var events = this._events;

  if (events !== undefined) {
    var evlistener = events[type];

    if (typeof evlistener === 'function') {
      return 1;
    } else if (evlistener !== undefined) {
      return evlistener.length;
    }
  }

  return 0;
}

EventEmitter.prototype.eventNames = function eventNames() {
  return this._eventsCount > 0 ? ReflectOwnKeys(this._events) : [];
};

function arrayClone(arr, n) {
  var copy = new Array(n);
  for (var i = 0; i < n; ++i)
    copy[i] = arr[i];
  return copy;
}

function spliceOne(list, index) {
  for (; index + 1 < list.length; index++)
    list[index] = list[index + 1];
  list.pop();
}

function unwrapListeners(arr) {
  var ret = new Array(arr.length);
  for (var i = 0; i < ret.length; ++i) {
    ret[i] = arr[i].listener || arr[i];
  }
  return ret;
}

function once(emitter, name) {
  return new Promise(function (resolve, reject) {
    function errorListener(err) {
      emitter.removeListener(name, resolver);
      reject(err);
    }

    function resolver() {
      if (typeof emitter.removeListener === 'function') {
        emitter.removeListener('error', errorListener);
      }
      resolve([].slice.call(arguments));
    };

    eventTargetAgnosticAddListener(emitter, name, resolver, { once: true });
    if (name !== 'error') {
      addErrorHandlerIfEventEmitter(emitter, errorListener, { once: true });
    }
  });
}

function addErrorHandlerIfEventEmitter(emitter, handler, flags) {
  if (typeof emitter.on === 'function') {
    eventTargetAgnosticAddListener(emitter, 'error', handler, flags);
  }
}

function eventTargetAgnosticAddListener(emitter, name, listener, flags) {
  if (typeof emitter.on === 'function') {
    if (flags.once) {
      emitter.once(name, listener);
    } else {
      emitter.on(name, listener);
    }
  } else if (typeof emitter.addEventListener === 'function') {
    // EventTarget does not have `error` event semantics like Node
    // EventEmitters, we do not listen for `error` events here.
    emitter.addEventListener(name, function wrapListener(arg) {
      // IE does not have builtin `{ once: true }` support so we
      // have to do it manually.
      if (flags.once) {
        emitter.removeEventListener(name, wrapListener);
      }
      listener(arg);
    });
  } else {
    throw new TypeError('The "emitter" argument must be of type EventEmitter. Received type ' + typeof emitter);
  }
}


/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var async_sema__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! async-sema */ "./node_modules/async-sema/lib/index.js");
/* harmony import */ var async_sema__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(async_sema__WEBPACK_IMPORTED_MODULE_0__);
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */



/**
 * How many tasks (workers) should be executed in parallel.
 */
let parallelLimit = 1;

/**
 * Set to true to stop automatic execution.
 *
 * @var {Boolean}
 */
var forceStop = false;

/**
 * Stores whether the stop was explicitly triggered by the user (pause click).
 *
 * @var {Boolean}
 */
var userPaused = false;

/**
 * Stores whether a task is already being actively executed.
 *
 * @var {Boolean}
 */
var taskActive = false;

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.jg-run-instant-task').forEach(button => {
    button.addEventListener('click', async (event) => {
      event.preventDefault();

      if (!taskActive) {
        const isNewRun = !forceStop;
        setPlayButtonState(button, 'pause');
        await runTask(event, button, isNewRun);
      } else {
        console.log('Task pause requested.');
        userPaused = true;
        forceStop = true;
        setPlayButtonState(button, 'play');
      }
    });
  });

  document.querySelectorAll('.jg-show-failed-items').forEach(link => {
    link.addEventListener('click', async (event) => {
      event.preventDefault();
      const taskId = link.dataset.taskId;
      const modalBody = document.getElementById('jg-failed-items-list');

      modalBody.innerHTML = '<p>Loading error list...</p>';

      try {
        const response = await getFailedItems(taskId);

        if (response.success && response.data) {
          if (response.data.items && response.data.items.length > 0) {
            let html = '<ul class="list-group">';
            response.data.items.forEach(item => {
              html += `<li class="list-group-item" data-item-id="${item.item_id || ''}"><strong>Item ${item.item_id || 'Unknown'}:</strong><br>${item.error_message || 'No error message'}</li>`;
            });
            html += '</ul>';
            modalBody.innerHTML = html;
          } else {
            modalBody.innerHTML = '<p>No failed items found.</p>';
          }
        } else {
          const errorMsg = response.data.error || response.message || 'Error loading data.';
          throw new Error(errorMsg);
        }
      } catch (error) {
        modalBody.innerHTML = `<p class="text-danger">An error occurred: ${error.message}</p>`;
      }
    });
  });

  document.querySelectorAll('[data-copy-failed-button]').forEach(button => {
    button.addEventListener('click', async (event) => {
      event.preventDefault();
      const listContainer = document.getElementById('jg-failed-items-list');
      if (!listContainer) {
        return;
      }

      const items = listContainer.querySelectorAll('li[data-item-id]');
      const itemIds = Array.from(items)
        .map(item => item.dataset.itemId)
        .filter(id => id)
        .join(',');

      if (itemIds) {
        try {
          await navigator.clipboard.writeText(itemIds);
          const originalTitle = button.getAttribute('title');
          const icon = button.querySelector('span.fa');
          button.setAttribute('title', 'Copied!');
          if (icon) {
            icon.classList.remove('fa-copy');
            icon.classList.add('fa-check');
          }
          setTimeout(() => {
            button.setAttribute('title', originalTitle);
            if (icon) {
              icon.classList.remove('fa-check');
              icon.classList.add('fa-copy');
            }
          }, 2000);
        } catch (err) {
          console.error('Failed to copy item IDs: ', err);
          alert('Failed to copy IDs to clipboard.');
        }
      }
    });
  });
});

/**
 * Sets the play/pause button to a specific state.
 *
 * @param {Element} button The button element
 * @param {String} state    'play' (shows play icon) or 'pause' (shows pause icon)
 */
let setPlayButtonState = function(button, state) {
  if (!button) return;
  const playIcon = button.querySelector('span.fa');
  if (!playIcon) return;

  if (state === 'pause') {
    playIcon.classList.remove('fa-play');
    playIcon.classList.add('fa-pause');
  } else {
    playIcon.classList.remove('fa-pause');
    playIcon.classList.add('fa-play');
  }
}

/**
 * A "worker loop" that continuously requests work.
 *
 * @param {int} workerId     Just for info
 * @param {String} taskId    The ID of the main task
 * @param {Sema} sema        The semaphore instance
 * @param {Element} logContainer The log element
 * @param {function} updateCounters Callback to update counters in runTask
 */
async function runWorkerLoop(workerId, taskId, sema, logContainer, updateCounters) {
  while (!forceStop) {
    const result = await processItem(taskId, sema, logContainer);

    if (result.status === 'success') {
      updateCounters('success', result.itemId);
    } else if (result.status === 'failed') {
      updateCounters('failed', result.itemId);
    } else if (result.status === 'no_work') {
      forceStop = true; // Stops all other workers
      break;
    } else if (result.status === 'network_error') {
      break;
    }
  }
}

/**
 * Processes ONE item. Fetches the job atomically from the backend.
 *
 * @param   {String}   taskId         The ID of the main task
 * @param   {Sema}     sema           The semaphore instance
 * @param   {Element}  logContainer   The log element
 * @returns {Object}   A status object
 */
async function processItem(taskId, sema, logContainer) {
  await sema.acquire();
  let itemId = null;

  try {
    const response = await ajax(taskId);

    if (response.success && response.data.success) {
      itemId = response.data.item_id;

      if (itemId === null) {
        addLog('No further items found. Worker finished.', logContainer, 'info');
        return { status: 'no_work' };
      }

      return { status: 'success', itemId: itemId };
    } else {
      itemId = response.data.item_id || 'Unknown';
      const errorMsg = response.data.error || response.message || 'Server reported an error';
      addLog(`Processing of item ${itemId} failed. Error: ${errorMsg}`, logContainer, 'error');
      return { status: 'failed', itemId: itemId };
    }
  } catch (error) {
    addLog(`Network/AJAX Error: ${error.message}`, logContainer, 'error');
    return { status: 'network_error' };
  } finally {
    sema.release();
  }
}


/**
 * Starts processing the queue for a specific task.
 *
 * @param {Object}  event     Event object
 * @param {Object}  element   The clicked button
 * @param {Boolean} isNewRun  True if the log should be cleared
 */
async function runTask(event, element, isNewRun = false) {
  if (taskActive) {
    alert('Another task is already running.');
    return;
  }

  taskActive = true;
  forceStop = false;
  userPaused = false;

  const taskId = element.dataset.id;
  const logContainer = document.getElementById('jg-modal-log-output');
  const workerCount = element.dataset.limit || parallelLimit;

  if (isNewRun) {
    clearLog(logContainer);
  }
  addLog('Task starting...', logContainer, 'info');
  startTaskUI(taskId);

  let successCount = parseInt(document.getElementById(`count-success-${taskId}`).textContent, 10) || 0;
  let failedCount = parseInt(document.getElementById(`count-failed-${taskId}`).textContent, 10) || 0;
  let pendingCount = parseInt(document.getElementById(`count-pending-${taskId}`).textContent, 10) || 0;
  const totalItems = successCount + failedCount + pendingCount;

  if (totalItems === 0 || (pendingCount === 0 && isNewRun)) {
    if (totalItems === 0) {
      addLog('Queue is empty. (No items found)', logContainer, 'info');
    } else {
      addLog('Queue is already finished. (No "Pending" items)', logContainer, 'info');
    }
    taskActive = false;
    finishTaskUI(taskId);
    setPlayButtonState(element, 'play');
    return;
  }

  addLog(`Starting ${workerCount} workers for ${pendingCount} remaining items...`, logContainer, 'info');

  const sema = new async_sema__WEBPACK_IMPORTED_MODULE_0__.Sema(workerCount);

  const updateCounters = (status, itemId) => {
    if (status === 'success') {
      successCount++;
    } else if (status === 'failed') {
      failedCount++;
    }
    if (pendingCount > 0) {
      pendingCount--;
    }
    updateTaskProgress(taskId, totalItems, successCount, failedCount, pendingCount);
  };

  updateTaskProgress(taskId, totalItems, successCount, failedCount, pendingCount);

  const promises = [];
  for (let i = 0; i < workerCount; i++) {
    promises.push(runWorkerLoop(i, taskId, sema, logContainer, updateCounters));
  }

  await Promise.allSettled(promises);

  // If not stopped by user (pause), clean up and reload
  if (!userPaused) {
    addLog('Cleaning up and reloading...', logContainer, 'info');
    const url = new URL(window.location.href);

    try {
      const res = await cleanupTask(taskId);
      const json = await res.json();

      if (json.success) {
        if (json.data && json.data.deleted === false) {
          console.warn('Task was not deleted:', json.data.reason);
        } else {
          url.searchParams.delete('newTaskId');
          window.location.replace(url.toString());
        }
      } else {
        alert('System error during cleanup: ' + json.message);
      }
    } catch (error) {
      console.error(error);
    } finally {
      finishTaskUI(taskId);
    }

    return;
  }

  addLog('Task processing paused.', logContainer, 'info');
  taskActive = false;
  finishTaskUI(taskId);
}

/**
 * Executes an Ajax request to process a single queue item.
 *
 * @param   {String}   taskId   The ID of the main task
 * @returns {Object}   The response object
 */
let ajax = async function(taskId) {
  let formData = new FormData(document.getElementById('adminForm'));

  formData.append('format', 'json');
  formData.append('task', 'task.runTask'); // Calls TaskController::runTask()
  formData.append('task_id', taskId);
  formData.append(Joomla.getOptions('csrf.token'), 1);

  let parameters = { method: 'POST', body: formData };
  let url = document.getElementById('adminForm').getAttribute('action');

  let response = await fetch(url, parameters);
  let txt = await response.text();
  let res = null;

  if (!response.ok) {
    return {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
  }

  if(txt.startsWith('{"success"')) {
    res = JSON.parse(txt);
    res.status = response.status;
    if (res.data) {
      try {
        res.data = JSON.parse(res.data);
      } catch (e) { /* is ok */ }
    }
  } else if (txt.includes('Fatal error')) {
    res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
  } else {
    let split = txt.split('\n{"');
    if (split.length > 1) {
      let temp  = JSON.parse('{"'+split[1]);
      let data  = JSON.parse(temp.data);
      res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
    } else {
      res = {success: false, status: response.status, message: 'Unknown response from server', messages: {}, data: {error: txt, data:null}};
    }
  }
  return res;
}

/**
 * Executes an Ajax request to get the list of failed items.
 *
 * @param   {String}   taskId   The ID of the main task
 * @returns {Object}   The response object
 */
let getFailedItems = async function(taskId) {
  let formData = new FormData(document.getElementById('adminForm'));

  formData.append('format', 'json');
  formData.append('task', 'task.getFailedItems'); // Calls TaskController::getFailedItems()
  formData.append('task_id', taskId);
  formData.append(Joomla.getOptions('csrf.token'), 1);

  let parameters = { method: 'POST', body: formData };
  let url = document.getElementById('adminForm').getAttribute('action');

  let response = await fetch(url, parameters);
  let txt = await response.text();
  let res = null;

  if (!response.ok) {
    return {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
  }

  if(txt.startsWith('{"success"')) {
    res = JSON.parse(txt);
    res.status = response.status;
    if (res.data) {
      try {
        res.data = JSON.parse(res.data);
      } catch (e) { }
    }
  } else if (txt.includes('Fatal error')) {
    res = {success: false, status: response.status, message: response.statusText, messages: {}, data: {error: txt, data:null}};
  } else {
    let split = txt.split('\n{"');
    if (split.length > 1) {
      let temp  = JSON.parse('{"'+split[1]);
      let data  = JSON.parse(temp.data);
      res = {success: true, status: response.status, message: split[0], messages: temp.messages, data: data};
    } else {
      res = {success: false, status: response.status, message: 'Unknown response from server', messages: {}, data: {error: txt, data:null}};
    }
  }
  return res;
}

/**
 * Adds a message to the log window.
 *
 * @param   {String}   msg             The message
 * @param   {Element}  logContainer    The DOM element for the log (in the modal)
 * @param   {String}   msgType         Type: 'error', 'warning', 'success', 'info'
 */
let addLog = function(msg, logContainer, msgType) {
  if (!msg || !logContainer) {
    return;
  }
  let line = document.createElement('p');
  const colorMap = {
    'error': 'text-danger',
    'warning': 'text-warning',
    'success': 'text-success',
    'info': 'text-muted'
  };
  line.className = colorMap[msgType] || 'text-dark';
  let msgTypeText = msgType.toLocaleUpperCase();
  line.textContent = `[${msgTypeText}] ${String(msg)}`;
  logContainer.appendChild(line);
  logContainer.scrollTop = logContainer.scrollHeight;
}

/**
 * Clears the log window.
 *
 * @param {Element} logContainer The DOM element for the log (in the modal)
 */
let clearLog = function(logContainer) {
  if (logContainer) {
    logContainer.innerHTML = '';
  }
}

/**
 * Updates the counters and the progress bar for a task.
 *
 * @param {String} taskId        The ID of the task
 * @param {int}    totalItems    Total number of items
 * @param {int}    successCount  Number of successful items
 * @param {int}    failedCount   Number of failed items
 * @param {int}    pendingCount  The number of remaining items
 */
let updateTaskProgress = function(taskId, totalItems, successCount, failedCount, pendingCount) {
  totalItems = totalItems || 0;
  successCount = successCount || 0;
  failedCount = failedCount || 0;
  pendingCount = (pendingCount < 0) ? 0 : pendingCount;

  let processedCount = successCount + failedCount;
  let progress = (totalItems > 0) ? Math.round((processedCount / totalItems) * 100) : 0;

  document.getElementById(`count-pending-${taskId}`).textContent = pendingCount;
  document.getElementById(`count-success-${taskId}`).textContent = successCount;
  document.getElementById(`count-failed-${taskId}`).textContent = failedCount;

  let bar = document.getElementById(`progress-${taskId}`);
  bar.style.width = progress + '%';
  bar.setAttribute('aria-valuenow', progress);
}

/**
 * Updates the UI when a task starts (only progress bar).
 *
 * @param {String} taskId The ID of the task
 */
let startTaskUI = function(taskId) {
  let bar = document.getElementById(`progress-${taskId}`);
  if (bar) {
    bar.classList.add('progress-bar-striped', 'progress-bar-animated');
  }
}

/**
 * Updates the UI when a task ends (progress bar and button).
 *
 * @param {String} taskId The ID of the task
 */
let finishTaskUI = function(taskId) {
  let startBtn = document.querySelector(`.jg-run-instant-task[data-id="${taskId}"]`);
  let bar = document.getElementById(`progress-${taskId}`);

  if (bar) {
    bar.classList.remove('progress-bar-striped', 'progress-bar-animated');
  }
  if (startBtn) {
    setPlayButtonState(startBtn, 'play');
  }
}

/**
 * Calls the cleanup endpoint.
 *
 * @param {String} taskId The ID of the task
 */
let cleanupTask = async function(taskId) {
  let formData = new FormData(document.getElementById('adminForm'));
  formData.append('format', 'json');
  formData.append('task', 'task.cleanupTask');
  formData.append('task_id', taskId);
  formData.append(Joomla.getOptions('csrf.token'), 1);

  return fetch(document.getElementById('adminForm').getAttribute('action'), {
    method: 'POST',
    body: formData
  });
}

})();

/******/ })()
;
//# sourceMappingURL=tasks.js.map