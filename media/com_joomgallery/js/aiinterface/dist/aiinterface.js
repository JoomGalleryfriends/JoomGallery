import * as __WEBPACK_EXTERNAL_MODULE_joomla_dialog_fc22b544__ from "joomla.dialog";
/******/ var __webpack_modules__ = ({

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


/***/ },

/***/ "joomla.dialog"
/*!********************************!*\
  !*** external "joomla.dialog" ***!
  \********************************/
(module) {

module.exports = __WEBPACK_EXTERNAL_MODULE_joomla_dialog_fc22b544__;

/***/ }

/******/ });
/************************************************************************/
/******/ // The module cache
/******/ var __webpack_module_cache__ = {};
/******/ 
/******/ // The require function
/******/ function __webpack_require__(moduleId) {
/******/ 	// Check if module is in cache
/******/ 	var cachedModule = __webpack_module_cache__[moduleId];
/******/ 	if (cachedModule !== undefined) {
/******/ 		return cachedModule.exports;
/******/ 	}
/******/ 	// Check if module exists (development only)
/******/ 	if (__webpack_modules__[moduleId] === undefined) {
/******/ 		var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 		e.code = 'MODULE_NOT_FOUND';
/******/ 		throw e;
/******/ 	}
/******/ 	// Create a new module (and put it into the cache)
/******/ 	var module = __webpack_module_cache__[moduleId] = {
/******/ 		// no module.id needed
/******/ 		// no module.loaded needed
/******/ 		exports: {}
/******/ 	};
/******/ 
/******/ 	// Execute the module function
/******/ 	__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 
/******/ 	// Return the exports of the module
/******/ 	return module.exports;
/******/ }
/******/ 
/************************************************************************/
/******/ /* webpack/runtime/compat get default export */
/******/ (() => {
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = (module) => {
/******/ 		var getter = module && module.__esModule ?
/******/ 			() => (module['default']) :
/******/ 			() => (module);
/******/ 		__webpack_require__.d(getter, { a: getter });
/******/ 		return getter;
/******/ 	};
/******/ })();
/******/ 
/******/ /* webpack/runtime/define property getters */
/******/ (() => {
/******/ 	// define getter functions for harmony exports
/******/ 	__webpack_require__.d = (exports, definition) => {
/******/ 		for(var key in definition) {
/******/ 			if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 				Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 			}
/******/ 		}
/******/ 	};
/******/ })();
/******/ 
/******/ /* webpack/runtime/hasOwnProperty shorthand */
/******/ (() => {
/******/ 	__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ })();
/******/ 
/******/ /* webpack/runtime/make namespace object */
/******/ (() => {
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ })();
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
/* harmony import */ var joomla_dialog__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! joomla.dialog */ "joomla.dialog");
// JoomGallery AIinterface class //



class AIinterface {
  // Settings
  name = 'JoomGallery AI Interface';
  prefix = 'jgai';
  host = 'localhost';
  systemLang = 'en';
  configs = {};
  lang = {};
  forceTrailingSlash = false;
  apiKeys = {};

  // Data from API
  token = '';
  info = {};
  balance = 0;
  models = [];
  modes = [];
  providers = {'localhost': 'Local', 'ollama' : 'Ollama/Local'};

  // Request info
  selected_model = 'gemma3:4b';
  selected_mode = 'performance';
  selected_lang = this.systemLang;
  client_name = 'JG-General';
  client_version = '1.0.0';

  // Image panel
  current_image = 0;

  constructor(prefix, host, token, client_name, configs, lang) {
    if (prefix) this.prefix = prefix;
    if (host) this.host = host;
    if (token) this.token = token;
    if (client_name) this.client_name = client_name;
    if (configs) this.configs = configs;
    if (lang) this.lang = lang;

    this.name = this.lang.COM_JOOMGALLERY_JS_AIINT_TITLE ?? 'JoomGallery AI Interface';

    // Detect language
    if(this.configs.def_lang) {
      if (this.configs.def_lang.includes('de')) this.systemLang = 'de';
    }

    // Detect client version
    if(this.configs.version) {
      this.client_version = this.configs.version;
    }

    // Detect trailing slash Settings
    if(this.configs.forceTrailingSlash) {
      this.forceTrailingSlash = this.configs.forceTrailingSlash;
    }

    // Detect api keys Settings
    if(this.configs.api_keys) {
      this.apiKeys = this.configs.api_keys;
    }
  }

  sanitizeUrl(url) {
    const hasQuery = url.includes('?');
    const hadTrailingSlash = /\/$/.test(url);

    // Collapse multiple slashes except after "http(s):"
    url = url.replace(/([^:])\/{2,}/g, "$1/");

    // Remove trailing slashes
    url = url.replace(/\/+$/g, "");

    if (!hasQuery && (hadTrailingSlash || this.forceTrailingSlash)) url += "/";

    return url;
  }

  capitalize(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
  }

  urlEncode(s) {
    return encodeURIComponent(String(s));
  }

  addHeader(headers = {}, addContentType = true) {
    const h = { ...headers };

    if (addContentType && !Object.prototype.hasOwnProperty.call(h, 'Content-Type')) {
      h['Content-Type'] = 'application/json';
    }
    if (!Object.prototype.hasOwnProperty.call(h, 'X-Client-Name')) {
      h['X-Client-Name'] = this.client_name;
    }
    if (!Object.prototype.hasOwnProperty.call(h, 'X-Client-Version')) {
      h['X-Client-Version'] = this.client_version;
    }

    return h;
  }

  logRequestErrors(url, headers, msg) {
    // Keep it similar to Lua logger intent
    console.debug("[JGAIInterface] Request failed");
    console.debug("URL:", url);
    console.debug("Message:", msg);
    console.debug("Headers:", headers);
  }

  parseJsonResponse(txt, status, statusText) {
    if (!txt) {
      return { success: false, status: status, message: statusText || "", messages: [], data: null };
    }

    // Clean JSON response (your old check was startsWith('{"success"'))
    const trimmed = txt.trim();

    // Catch Errors, warnings from API script
    if (txt.includes("Fatal error") || txt.includes("Warning") || txt.includes("Notice")) {
      return { success: false, status: status, message: statusText, messages: [ "Errors or warnings detected in response." ], data: txt };
    }
    else
    {
      try {
        const obj = JSON.parse(trimmed);

        // If API wraps JSON inside obj.data as string (your old code did JSON.parse(res.data))
        if (obj && typeof obj.data === "string") {
          try {
            obj.data = JSON.parse(obj.data);
          } catch {
            // leave as string if it isn't JSON
          }
        }

        return {success: true, status: status, message: statusText, messages: [], data: obj};
      } catch {
        // fall through to other heuristics
      }
    }

    // Unknown non-JSON response
    return { success: false, status: status, message: statusText, messages: ["Unknown non-JSON response."], data: txt };
  }

  addProviders(services) {
    for (const service of services || []) {
      if(service.value) {
        this.providers[service.value] = service.name;
      }
    }
  }

  buildTables(array, mapping) {
    const items = [];
    for (const rec of array || []) {
      const opts = {};
      for (const key of mapping.options || []) {
        opts[key] = rec[key];
      }

      items.push({
        value: String(rec[mapping.value] ?? "").toLowerCase(),
        title: rec[mapping.title],
        options: opts,
      });
    }
    return items;
  }

  buildModelTitles(models) {
    for (const m of models || []) {
      const provKey = (m.options && m.options.service) ? String(m.options.service) : "";
      const providerName = this.providers[provKey.toLowerCase()] || provKey;
      const baseName = m.value || m.title;
      m.title = `${baseName} (${providerName})`;
    }
  }

  summarizeModels(models) {
    const services = new Map(); // friendly -> [values]
    const order = [];

    for (const m of models || []) {
      let provider = (m.options && m.options.service) ? String(m.options.service) : "unknown";
      provider = provider.toLowerCase();

      const friendly = this.providers[provider] || provider;

      if (!services.has(friendly)) {
        services.set(friendly, []);
        order.push(friendly);
      }
      services.get(friendly).push(m.value);
    }

    const lines = order.map((prov) => `<strong>${prov}:</strong> ${services.get(prov).join(", ")}`);
    return { summary: lines.join("<br>"), providerCount: order.length };
  }

  getProvider(model, models = this.models) {
    const m = (models || []).find((x) => String(x.value).toLowerCase() === String(model).toLowerCase());
    return m?.options?.service;
  }

  getModelTitle(model, models = this.models) {
    const m = (models || []).find((x) => String(x.value).toLowerCase() === String(model).toLowerCase());
    return m?.title ?? model;
  }

  findAPIkey(model, models = this.models) {
    const m = (models || []).find((x) => String(x.value).toLowerCase() === String(model).toLowerCase());
    if (!m) return undefined;

    const provKey = (m.options && m.options.service) ? String(m.options.service) : "";
    const provLower = provKey.toLowerCase();

    if (provLower === "localhost" || provLower === "ollama") return "";

    const key = this.apiKeys?.[provLower];

    if (!key) {
      return false;
    }

    return key;
  }

  // --- DOM Helpers ----
  addEventListenerDropdown(el) {
    const dropdown = el.closest('ul');

    el.addEventListener('click', function (e) {
      e.preventDefault();

      // remove active + aria-selected from all
      dropdown.querySelectorAll('.dropdown-item').forEach(item => {
        item.classList.remove('active');
        item.setAttribute('aria-selected', 'false');
      });

      // add to clicked
      this.classList.add('active');
      this.setAttribute('aria-selected', 'true');
    });
  }

  addListElements(selector, items) {
    let dropdown = document.getElementById(this.prefix + selector);
    dropdown.innerHTML = '';

    // in case items contains a property called data, unpack it
    if(items?.data) {
      items = items.data;
    }

    items.forEach((item, index) => {
      let value, title, link;

      if (item && typeof item === 'object' && !Array.isArray(item)) {
        value = item.value ?? String(item).toLowerCase();
        title = item.title ?? this.capitalize(String(item.value ?? ''));
        link = item.link ?? '#';

        if (!value && item.title) {
          value = item.title.toLowerCase();
        }
        if (!title && item.value) {
          title = this.capitalize(String(item.value));
        }
      } else {
        const str = String(item);
        value = str.toLowerCase();
        title = this.capitalize(str);
        link = '#';
      }

      const li = document.createElement('li');
      const a = document.createElement('a');
      a.className = 'dropdown-item';
      a.href = link;
      a.dataset.value = value;
      a.textContent = title;

      // mark first item selected
      if (index === 0) {
        a.classList.add('active');
        a.setAttribute('aria-selected', 'true');
      } else {
        a.setAttribute('aria-selected', 'false');
      }

      li.appendChild(a);
      dropdown.appendChild(li);

      // install event listeners on a element
      this.addEventListenerDropdown(a);
    });
  }

  getSelectedListElement(selector) {
    const selected = document.querySelector(`#${selector} [aria-selected="true"]`);
    return selected ? selected.dataset.value : null;
  }

  getManualKeywords(container) {
    const keywords = [];
    const grid = container?.querySelector('.manual-keywords .grid');

    if (!grid) {
      return keywords;
    }

    grid.querySelectorAll('button').forEach((btn) => {
      const keyword = btn.innerText.trim();
      if (keyword) {
        keywords.push(keyword);
      }
    });

    return keywords;
  }

  getPanelPositionByImageId(imageId) {
  let pos = 0;

  document.querySelectorAll('.images-panel .image-panel').forEach((panel) => {
    const imageEl = panel.querySelector('img.image');

    if (imageEl && imageEl.getAttribute('data-imgid') == imageId) {
      const match = panel.getAttribute('id')?.match(/-image-panel-(\d+)$/);
      pos = match ? parseInt(match[1], 10) : 0;
    }
  });

  return pos;
  }

  manualKeywords(el, event) {
    event.preventDefault();

    const txtField = document.getElementById(`${this.prefix}-manual-keywords`);
    const keywords = txtField.value
      .split(',')
      .map(item => item.trim())
      .filter(item => item !== '');

    const grid = el.parentElement.parentElement.querySelector('.grid');

    const existingKeywords = Array.from(grid.querySelectorAll('button')).map(
      btn => btn.textContent.trim().toLowerCase()
    );

    keywords.forEach(keyword => {
      if (existingKeywords.includes(keyword.toLowerCase())) {
        return;
      }

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn btn-outline-primary';
      button.textContent = keyword;

      button.addEventListener('click', function () {
        this.remove();
      });

      grid.appendChild(button);
    });

    txtField.value = '';
  }

  updateImageNavigation() {
    const panels = document.querySelectorAll('.image-panel');
    const prevBtn = document.querySelector(`#${this.prefix}-prev-image-btn`);
    const nextBtn = document.querySelector(`#${this.prefix}-next-image-btn`);

    panels.forEach((panel, index) => {
      panel.style.display = index === this.current_image ? '' : 'none';
    });

    if (prevBtn) prevBtn.disabled = this.current_image === 0;
    if (nextBtn) nextBtn.disabled = this.current_image === panels.length - 1;
  }

  prevImage(el, event) {
    event.preventDefault();

    if (this.current_image > 0) {
      this.current_image--;
      this.updateImageNavigation();
    }
  }

  nextImage(el, event) {
    event.preventDefault();

    const panels = document.querySelectorAll('.image-panel');
    if (this.current_image < panels.length - 1) {
      this.current_image++;
      this.updateImageNavigation();
    }
  }

  async addKeywordsToImg(imgPos, keywords, color = 'black') {
    if (!Array.isArray(keywords) || !keywords.length) {
      console.log('Try to add keywords, but no keywords provided.');
      return;
    }

    // Get the panel
    const panel = document.querySelector(`#${this.prefix}-image-panel-${imgPos}`);
    if (!panel) {
      console.log(`Try to add keywords, but image panel at position ${imgPos} not found.`)
      return;
    }

    // Get the grid
    const grid = panel.querySelector('.grid');
    if (!grid) return;

    // Get image id from data attribute
    const imgID = panel.querySelector('.image').getAttribute('data-imgid');

    // Store keywords to image (ajax call)
    if(!(await this.storeKeywords(imgID, keywords, 'add')))
    {
      const keywords_str = keywords.join(', ');
      Joomla.renderMessages({ error: [`${this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_STORE_ERROR} ${this.lang.COM_JOOMGALLERY_JS_AIINT_KEYWORDS}: ${keywords_str}`] }, '#image-message-container');
      return;
    }

    // Collect existing keywords (case-insensitive)
    const existingKeywords = Array.from(
      grid.querySelectorAll('input[type="text"]')
    ).map(input => input.value.trim().toLowerCase());

    keywords.forEach((keyword, index) => {
      const value = keyword.trim();
      if (!value) return;

      // Skip duplicates
      if (existingKeywords.includes(value.toLowerCase())) return;

      // Generate a simple tag_id (you can replace this logic if needed)
      const tagId = Date.now() + '-' + index;

      // Create wrapper
      const wrapper = document.createElement('div');
      wrapper.className = 'input-group grid-item';

      // Create input
      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'form-control color-' + color;
      input.value = value;
      input.disabled = true;

      const inputId = `${this.prefix}-keyword-${imgID}-${tagId}`;
      input.id = inputId;

      // Create button
      const button = document.createElement('button');
      button.className = 'btn btn-outline-secondary';
      button.type = 'button';
      button.id = `${inputId}-btn`;
      button.textContent = 'X';

      // Add eventlistener to button
      button.addEventListener('click', (e) => this.removeKeyword(button, e));

      // Accessibility link
      input.setAttribute('aria-describedby', button.id);

      // Append elements
      wrapper.appendChild(input);
      wrapper.appendChild(button);
      grid.appendChild(wrapper);

      // Track to prevent duplicates within same call
      existingKeywords.push(value.toLowerCase());
    });
  }

  async remKeywordsFromImg(imgPos, keywords) {
    if (!Array.isArray(keywords) || !keywords.length) {
      console.log('Try to remove keywords, but no keywords provided.');
      return;
    }

    // Get the panel
    const panel = document.querySelector(`#${this.prefix}-image-panel-${imgPos}`);
    if (!panel) {
      console.log(`Try to remove keywords, but image panel at position ${imgPos} not found.`)
      return;
    }

    // Get the grid
    const grid = panel.querySelector('.grid');
    if (!grid) return;

    // Get image id from data attribute
    const imgID = panel.querySelector('.image').getAttribute('data-imgid');

    // Store keywords to image (ajax call)
    if(!(await this.storeKeywords(imgID, keywords, 'remove')))
    {
      const keywords_str = keywords.join(', ');
      Joomla.renderMessages({ error: [`${this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_REMOVE_ERROR} ${this.lang.COM_JOOMGALLERY_JS_AIINT_KEYWORDS}: ${keywords_str}`] }, '#image-message-container');
      return;
    }

    // Get all existing keywords
    const existingKeywords = Array.from(grid.querySelectorAll('input[type="text"]'));

    keywords.forEach((keyword, index) => {
      const value = keyword.trim();
      if (!value) return;

      // Remove existing ones
      const el = existingKeywords.find(input => input.value.trim() === value);
      if (el) {

        el.parentElement.remove();
      }
    });
  }

  addKeyword(el, event) {
    event.preventDefault();

    const keywords = [el.innerText.trim()];
    const pos      = this.current_image;
    this.addKeywordsToImg(pos, keywords, 'orange');
  }

  removeKeyword(el, event) {
    event.preventDefault();

    const inputID  = el.id.replace(/-btn$/, "");
    const input    = document.getElementById(inputID);
    const keywords = [input.value.trim()];
    const pos      = this.current_image;

    this.remKeywordsFromImg(pos, keywords);
  }

  async showAccount(el, event) {
    event.preventDefault();

    // Create models list
    const models = this.summarizeModels(this.models);

    // Fetch user info
    await this.getInfo();

    // Add content to modal
    document.getElementById(`${this.prefix}-modal-account-host`).textContent = this.sanitizeUrl(this.host);
    document.getElementById(`${this.prefix}-modal-account-models`).innerHTML = models.summary;
    document.getElementById(`${this.prefix}-modal-account-mail`).textContent = this.info.email;
    document.getElementById(`${this.prefix}-modal-account-balance`).textContent = this.balance;
    document.getElementById(`${this.prefix}-modal-account-infractions`).textContent = this.info.infractions;

    this.showModal('account');
  }

  async testConnection(el, event) {
    if (event) event.preventDefault();

    const url = this.sanitizeUrl(this.host);

    // Test ping
    const ping = await this.sendGet(url);

    if(ping.data !== "PING") {

      const title = this.lang.COM_JOOMGALLERY_JS_AIINT_CONN_FAILED_TITLE ?? 'Connection failed';
      const msg   = this.lang.COM_JOOMGALLERY_JS_AIINT_CONN_FAILED_TEXT + url;

      joomla_dialog__WEBPACK_IMPORTED_MODULE_1__["default"].alert(msg, title)
      .then(() => {
        console.log('Connection to "' + url + '" failed. Check your AI Interface host URL.');
      });
    } else {
      const info = await this.getInfo();

      if(info.status == 200) {
        const title = this.lang.COM_JOOMGALLERY_JS_AIINT_SUCCESS_TITLE ?? 'Success';
        const msg   = this.lang.COM_JOOMGALLERY_JS_AIINT_SUCCESS_TEXT + '  ' + url;

        joomla_dialog__WEBPACK_IMPORTED_MODULE_1__["default"].alert(msg, title)
      } else {
        const title = this.lang.COM_JOOMGALLERY_JS_AIINT_AUTH_FAILED_TITLE ?? 'Failed';
        const msg   = this.lang.COM_JOOMGALLERY_JS_AIINT_AUTH_FAILED_TEXT + '  ' + url;

        joomla_dialog__WEBPACK_IMPORTED_MODULE_1__["default"].alert(msg, title)
        .then(() => {
          console.log('Authentication to "' + url + '" failed. Check your AI Interface API-Key. Make sure your account in the AI Interface is up and running.');
        });
      }
    }
  }

  async keywordsGenerate(el, event) {
    event.preventDefault();

    // Get required data from UI
    let model  = this.getSelectedListElement(`${this.prefix}-models-dropdown`);
    let panels = document.querySelectorAll('.images-panel .image-panel');
    const manualKeywords = this.getManualKeywords(el.parentElement?.parentElement);

    if (!panels.length) {
      Joomla.renderMessages({ error: [this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_NO_PANELS_ERROR] }, '#image-message-container');
      return;
    }

    // Check that privacy terms are agreed
    const isChecked = document.getElementById(`${this.prefix}-privacy-box`).checked;
    if (!isChecked) {
      Joomla.renderMessages({ error: [this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_PRIVACY_ERROR] }, '#image-message-container');
      return;
    }

    // Create statistics object
    const stats = {
      total: panels.length,

      // Fetching base64 image
      fetchDone: 0,
      fetchSuccess: 0,
      fetchFailed: 0,

      // Fetching base64 image
      generateDone: 0,
      generateSuccess: 0,
      generateFailed: 0,

      createdKeywords: new Set(),
      modelTokens: 0,
      serviceTokens: 0,
      infractions: 0
    };

    // Create the options object
    const options = {
      'confidence_values': false,
      'model': model,
      'predefined_tags': manualKeywords,
      'prompt_mode': this.getSelectedListElement(`${this.prefix}-modes-dropdown`),
      'service': this.getProvider(model),
      'language': this.getSelectedListElement(`${this.prefix}-langs-dropdown`),
      'suggested_topic': document.getElementById(`${this.prefix}-prompt-description`)?.value ?? '',
      'tag_count': document.getElementById(`${this.prefix}-nmb-keywords`)?.value ?? '',
      'token_count': true
    }

    this.showModal('generate');
    this.showProgressSection();

    this.updateImageFetchProgress(stats.total, 0, 0, 0);
    this.updateKeywordGenerationProgress(stats.total, 0, 0, 0);

    const workerCount = Math.max(1, Number(this.configs.max_parallel || 1));

    // Generate keywords in parallel using sema
    const {
      results, successCount, failedCount
    } = await this.processImagesWithSema(
      panels, options, workerCount, stats
    );

    const errors = {};
    let success = failedCount === 0;

    // Check results
    results.forEach((result, index) => {
      if (!result?.success) {
        errors[String(result?.id ?? index)] = {
          status: result?.status ?? 0,
          error: result?.error ?? 'Unknown error'
        };
      }
    });

    // Gather the list of failed image items
    const failedItems = results
      .filter(r => !r.success)
      .map(r => ({
        id: r.id,
        error: r.error
      }));

    this.renderGenerateSummary({
      successImages: stats.generateSuccess,
      failedImages: stats.generateFailed,
      failedItems: failedItems,
      modelTitle: this.getModelTitle(model),
      keywords: Array.from(stats.createdKeywords),
      modelTokens: stats.modelTokens,
      serviceTokens: stats.serviceTokens,
      infractions: stats.infractions,
      newBalance: this.balance
    });

    // Update user info
    balance = await ai.getTokens();
    document.getElementById(prefix + '-balance-value').textContent = String(balance.data.balance);
  }

  async processSingleImageKeywords(panel, options, stats) {
    const fetched = await this.fetchImageAsBase64(panel);

    stats.fetchDone++;

    if (!fetched.success || !fetched.data) {

      // Failed image fetch
      stats.fetchFailed++;
      this.updateImageFetchProgress(
        stats.total,
        stats.fetchDone,
        stats.fetchSuccess,
        stats.fetchFailed
      );

      return {
        success: false,
        stage: 'fetch',
        id: fetched.id ?? null,
        status: fetched.status ?? 0,
        error: fetched.message || this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_FETCH_IMG_ERROR,
      };
    }

    // Successful image fetch
    stats.fetchSuccess++;
    this.updateImageFetchProgress(
      stats.total,
      stats.fetchDone,
      stats.fetchSuccess,
      stats.fetchFailed
    );

    const images = [
      {
        id: fetched.id,
        base64_data: fetched.data,
      }
    ];

    const response = await this.genKeywords(null, images, options);
    const payload = response?.data ?? {};
    const results = payload?.results ?? [];

    stats.generateDone++;

    if (!response?.success) {
      // Failed tags generation
      stats.generateFailed++;
      this.updateKeywordGenerationProgress(
        stats.total,
        stats.generateDone,
        stats.generateSuccess,
        stats.generateFailed
      );

      return {
        success: false,
        stage: 'generate',
        id: fetched.id,
        status: response?.status ?? 0,
        error: response?.message || this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_GEN_REQUEST_ERROR,
      };
    }

    if (response.status != 200 || !Array.isArray(results) || results.length < 1) {
      // Failed tags generation
      stats.generateFailed++;
      this.updateKeywordGenerationProgress(
        stats.total,
        stats.generateDone,
        stats.generateSuccess,
        stats.generateFailed
      );

      return {
        success: false,
        stage: 'generate',
        id: fetched.id,
        status: payload?.status ?? response?.status ?? 0,
        error: response?.message || this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_INVALID_RES_ERROR,
      };
    }

    const result = results[0];

    if (payload?.status != 1 || result.error) {
      // Failed tags generation
      stats.generateFailed++;
      this.updateKeywordGenerationProgress(
        stats.total,
        stats.generateDone,
        stats.generateSuccess,
        stats.generateFailed
      );

      return {
        success: false,
        stage: 'generate',
        id: result.id ?? fetched.id,
        status: payload?.status ?? response?.status ?? 0,
        error: result.error,
        model_tokens: result.model_tokens ?? payload.model_tokens ?? 0,
        service_tokens: result.service_tokens ?? payload.service_tokens ?? 0,
      };
    }

    const keywords = (result.tags || [])
      .map((tag) => tag?.name?.trim())
      .filter(Boolean);

    // Update stats
    keywords.forEach((kw) => stats.createdKeywords.add(kw));
    stats.modelTokens += Number(result.model_tokens ?? payload.model_tokens ?? 0);
    stats.serviceTokens += Number(result.service_tokens ?? payload.service_tokens ?? 0);
    stats.infractions = Number(payload.total_infractions ?? stats.infractions ?? 0);

    const pos = this.getPanelPositionByImageId(result.id ?? fetched.id);
    await this.addKeywordsToImg(pos, keywords, 'red');

    // Successful tags generation
    stats.generateSuccess++;
    this.updateKeywordGenerationProgress(
      stats.total,
      stats.generateDone,
      stats.generateSuccess,
      stats.generateFailed
    );

    return {
      success: true,
      stage: 'done',
      id: result.id ?? fetched.id,
      status: payload.status,
      keywords,
      model_tokens: result.model_tokens ?? payload.model_tokens ?? 0,
      service_tokens: result.service_tokens ?? payload.service_tokens ?? 0,
    };
  }

  async processImagesWithSema(panels, options, workerCount = 1, stats) {
    const sema = new async_sema__WEBPACK_IMPORTED_MODULE_0__.Sema(workerCount);
    const panelList = Array.from(panels);
    const results = new Array(panelList.length);

    let nextIndex = 0;
    let successCount = 0;
    let failedCount = 0;

    // Define worker loop function
    const runWorkerLoop = async () => {
      while (true) {
        const currentIndex = nextIndex++;
        if (currentIndex >= panelList.length) {
          break;
        }

        await sema.acquire();
        const panel = panelList[currentIndex];

        try {
          const result = await this.processSingleImageKeywords(panel, options, stats);
          results[currentIndex] = result;

          if (result.success) {
            successCount++;
          } else {
            failedCount++;
          }

        } catch (error) {
          results[currentIndex] = {
            success: false,
            stage: 'internal',
            id: null,
            status: 0,
            error: error instanceof Error ? error.message : String(error),
          };

          failedCount++;
        } finally {
          sema.release();
        }
      }
    };

    const workerTotal = Math.min(workerCount, panelList.length);
    const promises = [];

    // Run worker loop
    for (let i = 0; i < workerTotal; i++) {
      promises.push(runWorkerLoop());
    }

    // Wait for promise to resolve
    await Promise.allSettled(promises);

    return {
      results,
      successCount,
      failedCount
    };
  }

  // --- Generation Modal Helpers ----
  showModal(type) {
    const modalEl = document.getElementById(`${this.prefix}-modal-${type}`);
    if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
      console.error('Bootstrap Modal is not available.');
      return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
      backdrop: 'static',
      keyboard: false
    });

    modal.show();
  }

  showProgressSection() {
    document.getElementById(`${this.prefix}-progress-section`)?.classList.remove('d-none');
    document.getElementById(`${this.prefix}-summary-section`)?.classList.add('d-none');
  }

  showSummarySection() {
    document.getElementById(`${this.prefix}-progress-section`)?.classList.add('d-none');
    document.getElementById(`${this.prefix}-summary-section`)?.classList.remove('d-none');
  }

  updateImageFetchProgress(total, done, success, failed, pending) {
    const bar = document.getElementById(`${this.prefix}-progress-fetch-bar`);
    const text = document.getElementById(`${this.prefix}-progress-fetch-text`);

    if (!bar) return;

    const percent = Math.round((done / total) * 100);

    bar.style.width = percent + '%';
    bar.setAttribute('aria-valuenow', percent);
    bar.textContent = `${done} / ${total}`;

    text.textContent = `${this.lang.COM_JOOMGALLERY_JS_AIINT_PREPARED}: ${success}, ${this.lang.COM_JOOMGALLERY_JS_AIINT_FAILED}: ${failed}`;

    console.log(`[AIinterface] Keyword image fetch progress: total=${total}, success=${success}, failed=${failed}, pending=${pending}`);
  }

  updateKeywordGenerationProgress(total, done, success, failed, pending) {
    const bar = document.getElementById(`${this.prefix}-progress-generate-bar`);
    const text = document.getElementById(`${this.prefix}-progress-generate-text`);

    if (!bar) return;

    const percent = Math.round((done / total) * 100);

    bar.style.width = percent + '%';
    bar.setAttribute('aria-valuenow', percent);
    bar.textContent = `${done} / ${total}`;

    text.textContent = `${this.lang.COM_JOOMGALLERY_JS_AIINT_GENERATED}: ${success}, ${this.lang.COM_JOOMGALLERY_JS_AIINT_FAILED}: ${failed}`;

    console.log(`[AIinterface] Base64 image gereation progress: total=${total}, success=${success}, failed=${failed}, pending=${pending}`);
  }

  renderGenerateSummary(summary) {
    this.showSummarySection();

    const box  = document.getElementById(`${this.prefix}-summary-status`);
    const icon = document.getElementById(`${this.prefix}-summary-status-icon`);

    // Get success and failed numbers
    const success = summary.successImages ?? 0;
    const failed  = summary.failedImages ?? 0;

    // Change icon
    box.classList.remove('jgai-status-success', 'jgai-status-failed');
    if (failed === 0) {
      box.classList.add('jgai-status-success');
      icon.textContent = '✓';
    } else if (success === 0) {
      box.classList.add('jgai-status-failed');
      icon.textContent = '✕';
    } else {
      box.classList.add('jgai-status-warning');
      icon.textContent = '?';
    }

    // Generate list of failed images
    this.renderFailedImages(summary.failedItems);

    // Add summary content to table
    document.getElementById(`${this.prefix}-summary-success`).textContent = success;
    document.getElementById(`${this.prefix}-summary-failed`).textContent = failed;
    document.getElementById(`${this.prefix}-summary-model`).textContent = summary.modelTitle ?? summary.model ?? '-';
    document.getElementById(`${this.prefix}-summary-keywords`).textContent = (summary.keywords || []).join(', ') || '-';
    document.getElementById(`${this.prefix}-summary-model-tokens`).textContent = summary.modelTokens ?? 0;
    document.getElementById(`${this.prefix}-summary-service-tokens`).textContent = summary.serviceTokens ?? 0;
    document.getElementById(`${this.prefix}-summary-infractions`).textContent = summary.infractions ?? 0;
    document.getElementById(`${this.prefix}-summary-balance`).textContent = summary.newBalance ?? '-';
  }

  renderFailedImages(items) {
    const section = document.getElementById(`${this.prefix}-summary-failed-section`);
    const list    = document.getElementById(`${this.prefix}-summary-failed-list`);

    list.innerHTML = '';

    if (!items.length) {
      section.classList.add('d-none');
      return;
    }

    section.classList.remove('d-none');

    items.forEach(item => {
      const li = document.createElement('li');
      li.className = 'list-group-item text-danger';

      li.textContent = `${this.lang.COM_JOOMGALLERY_JS_AIINT_IMAGE}-ID ${item.id}: ${item.error}`;

      list.appendChild(li);
    });
  }

  // --- HTTP -------
  async sendGet(url, headers) {
    // Add default headers
    headers = this.addHeader(headers, false);

    // Set request parameters
    let params = {
      method: 'GET',
      cache: 'default',
      redirect: 'follow',
      referrerPolicy: 'no-referrer-when-downgrade',
      headers: headers
    };

    // Perform the fetch request
    let response;
    try {
      response = await fetch(url, params);
    } catch (e) {
      const msg = `Network error: ${String(e)}`;
      this.logRequestErrors(url, headers, msg);
      return { success: false, status: 0, message: msg, messages: [], data: null };
    }

    // Resolve promise as text string
    let txt = await response.text();

    if (!response.ok) {
      // Catch network error
      const msg = `HTTP ${response.status} ${response.statusText}`;
      this.logRequestErrors(url, headers, msg);
      return {success: false, status: response.status, message: msg, messages: [txt], data: null};
    }

    // Detect response content type (NOT request header)
    const responseContentType = response.headers.get('content-type') || '';

    // JSON response
    if (responseContentType.includes('application/json')) {
      return this.parseJsonResponse(txt, response.status, response.statusText);
    }

    // Plain text (base64 etc.)
    return {
      success: true,
      status: response.status,
      message: response.statusText,
      messages: [],
      data: txt
    };
  }

  async sendPost(url, bodyObjOrString, headers = {}) {
    headers = this.addHeader(headers);

    const contentType = headers["Content-Type"] || headers["content-type"] || "";
    let body = '';

    if(bodyObjOrString instanceof FormData)
    {
      body = bodyObjOrString;

      // Let the browser set multipart/form-data with boundary
      delete headers["Content-Type"];
      delete headers["content-type"];
    }
    else if(contentType.includes("application/json"))
    {
      body =
        typeof bodyObjOrString === "string"
          ? bodyObjOrString
          : JSON.stringify(bodyObjOrString ?? {});
    }
    else if(contentType.includes("application/x-www-form-urlencoded"))
    {
      body =
        typeof bodyObjOrString === "string"
          ? bodyObjOrString
          : new URLSearchParams(bodyObjOrString ?? {}).toString();
    }
    else
    {
      // Fallback: keep strings as-is, stringify plain objects
      body =
        typeof bodyObjOrString === "string"
          ? bodyObjOrString
          : JSON.stringify(bodyObjOrString ?? {});
    }

    const params = {
      method: "POST",
      cache: "default",
      redirect: "follow",
      referrerPolicy: "no-referrer-when-downgrade",
      headers,
      body,
    };

    let response;
    try {
      response = await fetch(url, params);
    } catch (e) {
      const msg = `Network error: ${String(e)}`;
      this.logRequestErrors(url, headers, msg);
      return { success: false, status: 0, message: msg, messages: [], data: null };
    }

    const txt = await response.text();

    if (!response.ok) {
      const msg = `HTTP ${response.status} ${response.statusText}`;
      this.logRequestErrors(url, headers, msg);
      return { success: false, status: response.status, message: msg, messages: [txt], data: null };
    }

    // Detect response content type (NOT request header)
    const responseContentType = response.headers.get('content-type') || '';

    // JSON response
    if (responseContentType.includes('application/json')) {
      return this.parseJsonResponse(txt, response.status, response.statusText);
    }

    // Plain text (base64 etc.)
    return {
      success: true,
      status: response.status,
      message: response.statusText,
      messages: [],
      data: txt
    };
  }

  async storeKeywords(imgId, keywords, action) {
    const url = this.sanitizeUrl(`${this.configs.base_url}/index.php?option=com_joomgallery&task=image.ajaxsavetags`);

    const headers = {
      'Authorization': '',
      'Content-Type': 'application/json',
      'X-CSRF-Token': this.configs.session
    };

    const formData = new FormData();
    formData.append('id', imgId);
    formData.append('action', action);

    keywords.forEach(keyword => {
      formData.append('keywords[]', keyword);
    });

    const res = await this.sendPost(url, formData, headers);

    if(!res.success) {
      console.log(res.error);
      console.log(res.data);

      if(action == 'add') {
        Joomla.renderMessages({'error':['Tags could not be added to image.']}, '#image-message-container');
      } else if(action == 'remove') {
        Joomla.renderMessages({'error':['Tags could not be removed from image.']}, '#image-message-container');
      }
    }
    else {
      if(action == 'add') {
        Joomla.renderMessages({'success':['Tags successfully stored to image.']}, '#image-message-container');
      } else if(action == 'remove') {
        Joomla.renderMessages({'success':['Tags successfully deleted from image.']}, '#image-message-container');
      }
    }

    return res.success
  }

  async fetchImageAsBase64(panel) {
    const imgEl = panel.querySelector('img.image');

    if (!imgEl) {
      return {
        success: false,
        status: null,
        message: 'No image element found in panel.',
        messages: [],
        data: null
      };
    }

    const imgID = imgEl.getAttribute('data-imgid');

    const variables = new URLSearchParams({
      id: imgID,
      type: this.configs.imagetype ?? '',
      base64: 1,
      resize: this.configs.resize ?? 0,
      resize_type: 3
    });

    const urlBase64 = this.sanitizeUrl(
      `${this.configs.base_url}/index.php?option=com_joomgallery&view=image&format=raw&${variables.toString()}`
    );

    const headersBase64 = {
      Authorization: '',
      'X-CSRF-Token': this.configs.session
    };

    const res = await this.sendGet(urlBase64, headersBase64);

    if (!res.success) {
      return {
        success: false,
        id: imgID,
        message: res.message || 'Image fetch failed.',
        data: null,
      };
    }

    return {
      success: true,
      id: imgID,
      message: 'OK',
      data: res.data,
    };
  }

  // --- API endpoints ----
  async getInfo(e) {
    if (e) e.preventDefault();

    const route = "users/info";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type": "application/json",
    };

    const data = await this.sendGet(url, headers);

    if (data?.data?.email) {
      this.info = data.data;
    } else if (data?.email) {
      this.info = data;
    }

    return data;
  }

  async getTokens(e) {
    if (e) e.preventDefault();

    const route = "/users/balance";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type" : "application/json"
    }

    const data = await this.sendGet(url, headers);

    if (data?.data?.balance) {
      this.balance = data.data.balance;
    } else if (data?.balance) {
      this.balance = data.balance;
    }

    return data;
  }

  async getLanguages(e) {
    if (e) e.preventDefault();

    const route = "/tags/languages";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type" : "application/json"
    }

    const data = await this.sendGet(url, headers);

    if (data?.data?.languages) {
      this.languages = data.data.languages;
    } else if (data?.languages) {
      this.languages = data.languages;
    }

    return data;
  }

  async getModels(e) {
    if (e) e.preventDefault();

    const route = "tags/models";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type": "application/json",
    };

    const data = await this.sendGet(url, headers);

    // Post-processing
    // If API returns {models:[{name,service,privacy}]} directly:
    if (data?.success !== false && data?.models) {
      // (Your Lua expects data.models; your older JS parsed res.data maybe.)
      const apiModels = data.models;

      if (apiModels.length > 0 && apiModels[0].value) {
        this.def_model = apiModels[0].value;
      }

      // Add services to providers table
      this.addProviders(data.services)

      const mapping = { value: "name", title: "service", options: ["service", "privacy"] };
      const mapped = this.buildTables(apiModels, mapping);
      this.buildModelTitles(mapped);

      this.models = mapped;
      return { ...data, mappedModels: mapped, def_model: this.def_model };
    }
    // If API returns wrapped data like {data:{models:[...]}}:
    if (data?.data?.models) {
      const apiModels = data.data.models;

      if (apiModels.length > 0 && apiModels[0].value) {
        this.def_model = apiModels[0].value;
      }

      const mapping = { value: "value", title: "title", options: ["service", "privacy", "modes"] };
      const mapped = this.buildTables(apiModels, mapping);
      this.buildModelTitles(mapped);

      // Extranct available modes
      mapped.forEach(model => {
        model.options?.modes?.forEach(mode => {
          if (!this.modes.includes(mode)) this.modes.push(mode);
        });
      });

      this.models = mapped;
      return mapped;
    }

    return data;
  }

  async genKeywords(e, images, options = {}) {
    if (e) e.preventDefault();

    const route = "tags/generate";
    const url = this.sanitizeUrl(`${this.host}/${route}`);

    const imgs = images || [];
    const usedModel = options.model || this.def_model;

    if (!Array.isArray(imgs) || imgs.length < 1) {
      return { success: false, status: 0, message: this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_NO_IMGS_ERROR, data: null };
    }

    const apiKey = this.findAPIkey(usedModel, this.models);
    if (apiKey === false) {
      return { success: false, status: 0, message: `${this.lang.COM_JOOMGALLERY_JS_AIINT_MSG_NO_API_KEY_ERROR} '${usedModel}'`, data: null };
    }

    const headers = {
      "Authorization": `Bearer ${this.token}`,
      "Content-Type": "application/json",
    };

    const body = {
      api_key: apiKey,
      options,
      images: imgs
    };

    return await this.sendPost(url, body, headers);
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  if (!window.Joomla) {
    throw new Error('Joomla API was not properly initialised');
  }

  // Example: pull config from Joomla options (adjust key to your code)
  const opts = Joomla.getOptions('com_joomgallery.aiinterface', {});
  const lang  = Joomla.getOptions('com_joomgallery.aiinterface.lang', {});
  const prefix = opts.prefix ?? 'jgai';
  const host = opts.host ?? 'localhost';
  const token = opts.token ?? '';
  const clientName = opts.client_name ?? 'JG-General';
  const configs = opts.configs ?? {};
  const session = opts.session ?? '';
  const baseURL = opts.base_url ?? '';
  const autoload = opts.autoload ?? false;
  let connected = false;
  let balance = 0;
  let models = {};
  let langs = {};

  window.Joomla.aiinterface = new AIinterface(prefix, host, token, clientName, configs, lang);
  let ai = window.Joomla.aiinterface;

  if(autoload) {
    // Check connction and get balance
    balance = await ai.getTokens();
    if(balance?.data?.balance) {
      connected = true;
      models = await ai.getModels();
      langs = await ai.getLanguages();

      // Update balance
      document.getElementById(prefix + '-balance-value').textContent = String(balance.data.balance);

      // Update models dropdown
      ai.addListElements('-models-dropdown', ai.models)

      // Update modes dropdown
      ai.addListElements('-modes-dropdown', ai.modes)

      // Update languages dropdown
      ai.addListElements('-langs-dropdown', ai.languages)
    } else {
      // Connection failed
      Joomla.renderMessages({warning: [undefined.lang.COM_JOOMGALLERY_JS_AIINT_MSG_NO_CONNECTION_ERROR]}, );

      if(balance?.message !== 'OK') {
        Joomla.renderMessages({error: [undefined.lang.COM_JOOMGALLERY_JS_AIINT_MSG_RESPOND_STATS + ': ' + balance.message]});
      }

      if(balance?.data?.messages.length > 0) {
        Joomla.renderMessages({warning: [undefined.lang.COM_JOOMGALLERY_JS_AIINT_MSG_API_ANSWER + ': ' + balance.data.messages[0]]});
      }
    }
  }

  // Install event listeners on dropdowns
  document.querySelectorAll('[id^="'+prefix+'-"][id$="-dropdown"]').forEach(el => {
    el.querySelectorAll('.dropdown-item').forEach(item => {
      ai.addEventListenerDropdown(item);
    });
  });

  // Install event listeners on buttons
  document.querySelectorAll('[id^="'+prefix+'-"][id$="-btn"]').forEach(el => {
    const name = el.id.split('-').slice(1, -1).join('-');
    const fn = name.replace(/-([a-z])/g, (_, c) => c.toUpperCase());

    // Image Keyword Button
    let match = name.match(new RegExp(`^keyword-(\\d+)-(\\d+)$`));
    if (match) {
      const [, imgId, tagId] = match;
      el.addEventListener('click', (e) => ai.removeKeyword(el, e));
      return;
    }

    // Manual Keyword Button
    match = name.match(new RegExp(`^manual-keyword-(\\d+)$`));
    if (match) {
      el.addEventListener('click', (e) => ai.removeManualKeyword(el, e));
      return;
    }

    // Most used Keywords Button
    match = name.match(new RegExp(`^keywords-list-(\\d+)$`));
    if (match) {
      el.addEventListener('click', (e) => ai.addKeyword(el, e));
      return;
    }

    // Any other Button
    if (typeof ai[fn] === 'function') {
      el.addEventListener('click', (e) => ai[fn](el, e));
    } else {
      console.warn(`AIinterface: function ${fn}() does not exist. The corresponding button will not work.`);
    }
  });
})

})();


//# sourceMappingURL=aiinterface.js.map