{if isset($hco_status_substrings)}
    <script>
        window.hcoStatusSubstrings = {literal}{/literal}{$hco_status_substrings|json_encode}{literal}{/literal};
    </script>
{/if}