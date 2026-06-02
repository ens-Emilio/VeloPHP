<?php $__engine->extends('layouts/main'); ?>

<?php $__engine->section('content'); ?>

<h2>Bem-vindo ao <?= e($name ?? 'VeloPHP') ?>!</h2>
<p style="margin-top: 1rem; color: #a0a0a0; line-height: 1.8;">
    Framework PHP customizado com arquitetura moderna e performática.
</p>

<div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
    <div style="background: #1a1a2e; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #00d4ff;">
        <h3 style="color: #00d4ff;">Router</h3>
        <p style="color: #a0a0a0; margin-top: 0.5rem;">Groups, middleware pipeline, named routes</p>
    </div>
    <div style="background: #1a1a2e; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ffd93d;">
        <h3 style="color: #ffd93d;">Container DI</h3>
        <p style="color: #a0a0a0; margin-top: 0.5rem;">Auto-resolucao, singletons, service providers</p>
    </div>
    <div style="background: #1a1a2e; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ff6b6b;">
        <h3 style="color: #ff6b6b;">Database</h3>
        <p style="color: #a0a0a0; margin-top: 0.5rem;">Query builder fluente com prepared statements</p>
    </div>
    <div style="background: #1a1a2e; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #6bff6b;">
        <h3 style="color: #6bff6b;">Seguranca</h3>
        <p style="color: #a0a0a0; margin-top: 0.5rem;">CSRF, validacao, sessions, rate limiting</p>
    </div>
</div>

<?php $__engine->endSection(); ?>
