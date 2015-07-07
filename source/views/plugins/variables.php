<div class="plugin" id="profiler-plugin-variables">
    <div class="title top-title">[[Request and Environment Variables]]</div>
    <?
    /**
     * @var \Psr\Http\Message\ServerRequestInterface $request
     */
    ?>
    <div class="tabs-block">
        <div class="tab-navigation">
            <ul id="tabs">
                <li>
                    <a href="#tab1">First Tab</a>
                </li>
                <li>
                    <a href="#tab2">Second Tab</a>
                </li>
                <li>
                    <a href="#tab3">Third Tab</a>
                </li>
            </ul>
        </div>
        <div class="tab-content">
            <div class="tab-block" id="tab1">
                <pre>
 user@linux:~/files/blog> mysqldump --add-drop-table -h mysqlhostserver
 -u mysqlusername -p databasename (tablename tablename tablename) | bzip2
 -c > blog.bak.sql.bz2

Enter password: (enter your mysql password)
user@linux~/files/blog>
                </pre>
            </div>
            <div class="tab-block" id="tab2">
                lorem ipsum 2
            </div>
            <div class="tab-block" id="tab3">
                lorem ipsum 3
            </div>
        </div>
    </div>
</div>