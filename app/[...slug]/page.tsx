import { Storefront } from "../storefront";

export default async function DynamicPage({
  params,
}: {
  params: Promise<{ slug: string[] }>;
}) {
  const { slug } = await params;
  return <Storefront path={`/${slug.join("/")}`} />;
}
