{extends "$layout"}

{block name="content"}


{literal}
<style type="text/css">
    #statusMessageContainer {
        text-align: center;
    }

    #statusMessageContainer p {
        text-align: right;
    }

    .lds-ring {
        display: inline-block;
        position: relative;
        width: 80px;
        height: 80px;
    }

    .lds-ring div {
        box-sizing: border-box;
        display: block;
        position: absolute;
        width: 64px;
        height: 64px;
        margin: 8px;
        border: 8px solid #fff;
        border-radius: 50%;
        animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        border-color: #fff transparent transparent transparent;
    }

    .lds-ring div:nth-child(1) {
        animation-delay: -0.45s;
    }

    .lds-ring div:nth-child(2) {
        animation-delay: -0.3s;
    }

    .lds-ring div:nth-child(3) {
        animation-delay: -0.15s;
    }

    @keyframes lds-ring {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>
{/literal}
<h2 class="page-heading">خطا</h2>
{if $error != null}
<div id="statusMessageContainer">
    <p class="alert alert-danger">{$error|escape:'htmlall':'UTF-8'}</p>
    {literal}
    <script type="text/javascript">
                                                                                      setTimeout(function(){location.href="{/literal}{$redirectUrl}{literal}";}, 4000);
    </script>
    {/literal}

</div>
<a href="{$link->getPageLink('index', true, null)}" class="btn btn-warning zarinpalpayment-back-button">{l s='Back to main mage' mod='zarinpalpayment'}</a>
{else}
<h2 class="page-heading">در حال انتقال . لطفا منتظر بمانید</h2>
{literal}
<script type="text/javascript">
                     setTimeout(function(){document.forms["redirectpost"].submit();}, 1000);
</script>
{/literal}

<body onload="redirectToBanck();">
    <form name="redirectpost" method="post" action="Location: https://www.zarinpal.com/pg/StartPay/" enctype="‫‪multipart/form-data‬‬">
        <input type="hidden" name="Authority" value="{$authority}" />
    </form>
</body>';
<div class="lds-ring">
    <div></div>
    <div></div>
    <div></div>
    <div></div>
</div>
{/if}
{/block}