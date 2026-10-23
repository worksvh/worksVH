import { requireUser, mediaUsage, loadUsageRows } from '@/lib/admin'
import { all } from '@/lib/db'
import { mediaUrl } from '@/lib/storage'
import type { Media } from '@/lib/content'
import { AdminShell, Csrf } from '@/components/admin/AdminShell'

export default async function MediaPage() {
  const user = await requireUser('/admin/media')
  const rows = await all<Media & { variants: string }>('SELECT * FROM media ORDER BY id DESC')
  const usageRows = await loadUsageRows()
  const media = await Promise.all(rows.map(async (m) => {
    const variants = JSON.parse((m.variants as unknown as string) || '[]') as Media['variants']
    return { ...m, variants, usage: await mediaUsage(m.id, usageRows) }
  }))

  return (
    <AdminShell user={user} title="Média" nav="media">
      <div className="a-head">
        <div>
          <h1>Média</h1>
          <p className="help">Obrázky pro web. JPG, PNG a WebP do 10 MB, SVG jen pro logo. Po nahrání se vytvoří menší verze pro rychlé načítání.</p>
        </div>
      </div>

      <form method="post" action="/admin/api/media/upload" encType="multipart/form-data" className="box upload-box" data-media-upload>
        <Csrf />
        <div className="f-field"><label htmlFor="m-file">Obrázky</label><input type="file" id="m-file" name="file" multiple accept=".jpg,.jpeg,.png,.webp,.svg" required /></div>
        <div className="f-field"><label htmlFor="m-alt">Popis (alt) – nepovinné, jde doplnit i později</label><input type="text" id="m-alt" name="alt" /></div>
        <button type="submit" className="btn-primary">Nahrát</button>
        <p className="help" data-media-upload-state role="status"></p>
      </form>

      <div className="list-tools"><label htmlFor="media-search" className="sr">Hledat</label><input type="search" id="media-search" placeholder="Hledat podle názvu nebo popisu…" data-media-search /></div>

      {!media.length && <p>Zatím žádné obrázky.</p>}
      <div className="media-grid">
        {media.map((m) => {
          const thumb = mediaUrl(m.variants.length ? m.variants[0][2] : m.filename)
          return (
            <article key={m.id} className="media-card" id={`m-${m.id}`} data-search={(m.original_name + ' ' + m.alt).toLowerCase()}>
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <a href={mediaUrl(m.filename)} target="_blank" rel="noopener" className="media-thumb"><img src={thumb} alt="" loading="lazy" /></a>
              <div className="media-meta">
                <p className="strong media-name">{m.original_name}</p>
                <p className="help">
                  {m.width ? `${m.width} × ${m.height} px · ` : ''}{(m.filename.split('.').pop() || '').toUpperCase()}
                  {m.variants.length ? ` · ${m.variants.length} verze WebP` : ''}
                </p>
                <form method="post" action={`/admin/api/media/${m.id}`} className="media-alt">
                  <Csrf />
                  <label htmlFor={`alt-${m.id}`} className="sub-label">Popis (alt){m.alt === '' && <> <span className="badge-todo">chybí</span></>}</label>
                  <input type="text" id={`alt-${m.id}`} name="alt" defaultValue={m.alt} />
                  <button type="submit" className="btn-small btn-ghost">Uložit popis</button>
                </form>
                <div className="media-usage">
                  <span className="sub-label">Použito:</span>
                  {!m.usage.length ? <span className="help">nikde</span> : (
                    <ul>{m.usage.map(([label, url], i) => <li key={i}><a href={url}>{label}</a></li>)}</ul>
                  )}
                </div>
                {!m.usage.length ? (
                  <form method="post" action={`/admin/api/media/${m.id}/delete`} data-confirm={`Opravdu smazat obrázek ${m.original_name}?`}>
                    <Csrf /><button type="submit" className="btn-small btn-danger">Smazat</button>
                  </form>
                ) : <p className="help">Použitý obrázek nejde smazat.</p>}
              </div>
            </article>
          )
        })}
      </div>
    </AdminShell>
  )
}
