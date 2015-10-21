{if !empty($errors)}
<div class="error"><p>&nbsp;{$errors|escape:'UTF-8'}</p></div>
{/if}

{if !empty($title_message) && !empty($js_message)}
    <div class="error"><p>&nbsp;{$errors|escape:'UTF-8'}</p></div>

    <fieldset><legend>Store Commander</legend>
        <label>{$title_message|escape:'UTF-8'}</label>
        <div class="margin-form">
            <script>
                {$js_message|escape:'UTF-8'}
            </script>
        </div>
    </fieldset>
{/if}

