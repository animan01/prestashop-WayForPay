{l s='Очікує перенаправлення' mod='wayforpay'}
<form id="wayforpay_payment" method="post" action="{$url}">
  {foreach from=$fields  key=key item=field}
    {if $field|is_array}
      {foreach from=$field  key=k item=v}<input type="hidden" name="{$key}[]" value="{$v}" />{/foreach}
    {else}
      <input type="hidden" name="{$key}" value="{$field}"/>
    {/if}
  {/foreach}

  <input type="submit" value="{l s='Оплатити' mod='wayforpay'}">
</form>
<script type="text/javascript" src="/modules/wayforpay/js/redirect-page.js"></script>
