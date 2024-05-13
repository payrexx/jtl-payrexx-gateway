<form method="post" action="{$postUrl}" class="navbar-form">
    <input type="hidden" name="validate"/>
    <div class="settings-content">
        <div class="first">
            {if isset($valid)}
                {if $valid}
                    <div class="alert alert-success">
                        <font style="vertical-align: inherit;">
                            {$languageTexts.jtl_payrexx_signature_check_success}
                        </font>
                    </div>
                {else}
                    <div class="alert alert-danger">
                        <font style="vertical-align: inherit;">
                            {$languageTexts.jtl_payrexx_signature_check_fail}
                        </font>
                    </div>
                {/if}
            {/if}
            <hr class="mb-3">
        </div>
    </div>
    <div class="save-wrapper">
        <div class="row">
            <div class="ml-auto col-sm-6 col-xl-auto">
                    <button name="speichern" type="submit" value="Save" class="btn btn-primary btn-block">
                    {$languageTexts.jtl_signature_check_submit}
                </button>
            </div>
        </div>
    </div>
</form>