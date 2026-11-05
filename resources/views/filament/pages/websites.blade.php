<x-filament-panels::page>
    <x-filament::section heading="Website launch guide" icon="heroicon-o-question-mark-circle" collapsible collapsed>
        <p>Start with a domain, then configure hosting and deploy your application. Use the Needs attention filter to find missing configuration, expiring certificates, and failed deployments.</p>
        <ol>
            <li>Add your domain and select the hosting account assigned by your administrator.</li>
            <li>Configure the web runtime and document root, then point your domain's DNS records at the hosting server.</li>
            <li>Deploy your files or connect your application repository, request an HTTPS certificate, and test your website.</li>
            <li>Set up scheduled backups and verify that you can restore them.</li>
        </ol>
        <details>
            <summary>Advanced deployment: Git, containers, and Kubernetes</summary>
            <p>For GitHub or GitLab, configure a repository, branch, and deployment path in your hosting configuration. Use credentials limited to that repository and keep secrets out of repository URLs.</p>
            <p>For Docker or Kubernetes workloads, ask your administrator to connect an approved container host or cluster and assign access. Deploying the control panel in a container does not automatically create hosting workloads.</p>
            <p>Review deployment results and test the live site after each release. Configuration records alone do not confirm that DNS, HTTPS, or your application is working.</p>
        </details>
    </x-filament::section>
    {{ $this->table }}
</x-filament-panels::page>
