/* Logování do konzole (na Vercelu ho najdete v Logs projektu). */
export function log(channel: 'error' | 'info' | 'mail' | 'spam', msg: string): void {
  const line = `[${channel}] ${msg}`
  if (channel === 'error') console.error(line)
  else console.log(line)
}
