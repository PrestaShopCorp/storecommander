<link type="text/css" rel="stylesheet" href="../modules/{$module_name}/views/css/admin.css" />

<script type="text/javascript" src="../modules/{$module_name}/views/js/loader/jquery.loader-min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $(".loading").click(function() {
            $.loader({
                className:"blue-with-image-2",
                content:""
            });
        });
    });
</script>