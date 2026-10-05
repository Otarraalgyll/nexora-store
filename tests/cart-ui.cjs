// Regression checks for the actual AJAX submit handler, without browser dependencies.
// Run: node tests/cart-ui.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const code = fs.readFileSync(require('node:path').join(__dirname, '../assets/js/main.js'), 'utf8');
async function check(action, hiddenAction = false) {
  let submit, sent, redirected;
  const classes = {add(){}, remove(){}, toggle(){}};
  const button = {name: hiddenAction ? '' : 'action', value: action, disabled: false, classList: classes};
  const count = {textContent: '0'};
  const total = {dataset: {unit: '2790'}, textContent: ''};
  const row = {querySelector: () => total, remove(){this.removed = true;}};
  const form = {
    // This models the browser's named-control override. Using form.action is a bug.
    action: {toString: () => '[object RadioNodeList]'},
    dataset: {},
    getAttribute: name => name === 'action' ? '/action.php' : null,
    addEventListener: (name, handler) => {if (name === 'submit') submit = handler;},
    querySelectorAll: () => [button], querySelector: () => null,
    closest: () => row
  };
  const toast = {classList: classes, textContent: ''};
  const document = {
    querySelector: selector => selector === 'meta[name="base-url"]' ? {content: ''} : null,
    querySelectorAll: selector => selector === 'form[data-ajax]' ? [form] : selector === '.cart-count' ? [count] : [],
    getElementById: id => id === 'toast' ? toast : null,
    addEventListener(){}
  };
  class FormDataStub extends Map {
    constructor() {super([['quantity', '2']]); if(hiddenAction) this.set('action',action);}
  }
  vm.runInNewContext(code, {
    document, window: {addEventListener(){}, scrollY:0},
    location: {href:'http://localhost/shop.php', pathname:'/shop.php', assign: url => redirected=url},
    FormData: FormDataStub, setTimeout:()=>1, clearTimeout(){},
    fetch: async (url, options) => {
      sent = {url, options};
      return {ok:true, json:async()=>({ok:true, count:2, totals:{}, message:'Saved.', redirect:action==='buy'?'/checkout.php':null})};
    }
  });
  await submit({preventDefault(){}, submitter:button});
  assert.equal(sent.url, '/action.php', 'Submit to the endpoint, not the action-named control');
  assert.equal(sent.options.body.get('action'), action);
  assert.equal(button.disabled, false);
  assert.equal(form.dataset.busy, undefined);
  if(action==='buy') assert.equal(redirected,'/checkout.php');
  else assert.equal(count.textContent,2);
  if(action==='cart_update') assert.match(total.textContent,/5,580/);
  if(action==='cart_remove') assert.equal(row.removed,true);
  console.log('PASS AJAX UI '+action);
}
(async()=>{for(const action of ['add','buy','wishlist','cart_update','cart_remove']) await check(action,action==='wishlist');})().catch(error=>{console.error(error);process.exitCode=1;});
