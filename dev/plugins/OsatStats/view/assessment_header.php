<?php if(is_object($assessment)): ?>
<div class="osatstats--header">
    <div class="center">
        <h2 class="osatstats--header--title">{{Personal document for:}}</h2>
        <p class="osatstats--header--owner">
            <?php echo htmlspecialchars($assessment->get('tokenData')->firstname); ?> <?php echo htmlspecialchars($assessment->get('tokenData')->lastname); ?>
            <?php if($t = $assessment->get('tokenData')->attribute_1): ?><br /><?php echo htmlspecialchars($t); ?><?php endif; ?>
        </p>
        <span class="osatstats--header--print">
            <a href="javascript:window.print()" class="btn btn-default jspdf-print" data-jspdf-options="&#123;&#34;watermarktext&#34;:&#34;{{Individual document}}&#34;&#125;">{{Print}}</a>
        </span>
    </div>
</div>
<?php endif; ?>
