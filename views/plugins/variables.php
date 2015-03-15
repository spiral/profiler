<div class="plugin" id="profiler-plugin-variables">
    <div class="title top-title">Request Variables</div>
    <?
    /**
     * @var \Psr\Http\Message\ServerRequestInterface $request
     */
    ?>
    <div class="clearfix">
        <div style="width: 50%; float: left; display: inline-block;">
            <div class="small-title">GET</div>
            <?php dump($request->getQueryParams()); ?>
        </div>
        <div style="width: 50%; float: left; display: inline-block;">
            <div class="small-title">REQUEST BODY (POST)</div>
            <?php dump($request->getParsedBody()); ?>
        </div>
    </div>
    <div class="clearfix">
        <div style="width: 50%; float: left; display: inline-block;">
            <div class="small-title">COOKIES</div>
            <?php dump($request->getCookieParams()); ?>
        </div>
        <div style="width: 50%; float: left; display: inline-block;">
            <div class="small-title" style="margin-bottom: 10px;">SESSION</div>
            <?php
            if (empty($_SESSION))
            {
                echo 'Session is not started.';
            }
            else
            {
                dump($_SESSION);
            }
            ?>
        </div>
    </div>
    <div class="clearfix">
        <div class="small-title">REQUEST HEADERS</div>
        <?php
        $headers = $request->getHeaders();

        array_walk($headers, function (&$values)
        {
            $values = join(',', $values);
        });

        dump($headers);
        ?>
    </div>
</div>